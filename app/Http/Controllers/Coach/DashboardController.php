<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Subscription;

class DashboardController extends Controller
{
    public function index()
    {
        $coach = auth()->user();
        $profile = $coach->ensureCoachProfile();

        $clients = Subscription::with(['trainee', 'workoutPlan', 'nutritionPlan'])
            ->where('coach_id', $coach->id)
            ->running()
            ->orderBy('starts_at')
            ->get();

        $recent = Subscription::with('trainee')
            ->where('coach_id', $coach->id)
            ->confirmed()
            ->where('ends_at', '<=', now())
            ->whereNotIn('trainee_id', $clients->pluck('trainee_id'))   // renewed ones are still clients
            ->latest('ends_at')
            ->limit(10)
            ->get();

        // per client: check-ins waiting for a reply, and days late for the next one
        $byTrainee = CheckIn::where('coach_id', $coach->id)->whereIn('trainee_id', $clients->pluck('trainee_id'))
            ->latest()->latest('id')->get()->groupBy('trainee_id');
        $checkIns = $clients->mapWithKeys(function (Subscription $sub) use ($byTrainee) {
            $list = $byTrainee->get($sub->trainee_id, collect());
            $due = $sub->checkInSchedule($list->first())['due'];

            return [$sub->id => [
                'unanswered' => $list->whereNull('replied_at')->count(),
                'late_days'  => today()->gt($due) ? (int) $due->diffInDays(today()) : 0,
            ]];
        });

        $renewals = $clients->mapWithKeys(fn (Subscription $sub) => [$sub->id => [
            'renewed'  => $sub->renewal()?->status === 'active',
            'daysLeft' => $sub->daysLeft(),
        ]]);

        return view('coach.dashboard', [
            'checkIns'     => $checkIns,
            'renewals'     => $renewals,
            'toAnswer'     => $checkIns->sum('unanswered'),
            'coach'        => $coach,
            'profile'      => $profile,
            'missing'      => $profile->missingSteps(),
            'clients'      => $clients,
            'waitingPlans' => $clients->filter(fn ($s) => !empty($s->missingPlans()))->count(),
            'recent'       => $recent,
            'earnings'     => $coach->coachEarnings(),
        ]);
    }
}
