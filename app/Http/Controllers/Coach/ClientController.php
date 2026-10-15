<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\CheckInController;
use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Subscription;

class ClientController extends Controller
{
    public function show(Subscription $subscription)
    {
        abort_unless($subscription->coach_id === auth()->id() && $subscription->status === 'active', 404);

        $subscription->load(['trainee', 'workoutPlan', 'nutritionPlan', 'review']);

        $previous = Subscription::where('coach_id', auth()->id())
            ->where('trainee_id', $subscription->trainee_id)
            ->confirmed()
            ->whereKeyNot($subscription->id)
            ->latest('starts_at')
            ->get();

        $checkIns = CheckIn::between($subscription->trainee_id, $subscription->coach_id)->get();
        $schedule = $subscription->checkInSchedule($checkIns->last());
        $trainee = $subscription->trainee;

        return view('coach.client', [
            'subscription' => $subscription,
            'previous'     => $previous,
            'checkIns'     => $checkIns->reverse()->values(),
            'points'       => CheckInController::chartPoints($subscription, $checkIns),
            'schedule'     => $schedule,
            'lateDays'     => today()->lt($schedule['due']) ? null : (int) $schedule['due']->diffInDays(today()),
            'remindUrl'    => $trainee->whatsapp_url ? $trainee->whatsapp_url . '?text=' . rawurlencode(__(
                'Hi :name, time for your weekly check-in: :url', ['name' => $trainee->name, 'url' => route('check-ins.show', $subscription)]
            )) : null,
        ]);
    }
}
