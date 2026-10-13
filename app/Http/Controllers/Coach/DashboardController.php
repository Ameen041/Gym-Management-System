<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
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
            ->latest('ends_at')
            ->limit(10)
            ->get();

        return view('coach.dashboard', [
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
