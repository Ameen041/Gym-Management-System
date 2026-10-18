<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\CheckInController;
use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\Conversation;
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

        $last = $subscription->previousWithCoach()?->load(['workoutPlan', 'nutritionPlan']);
        $conversation = Conversation::where('coach_id', $subscription->coach_id)->where('trainee_id', $subscription->trainee_id)->first();
        $renewal = $subscription->renewal();

        return view('coach.client', [
            'subscription' => $subscription,
            'previous'     => $previous,
            'lastPlans'    => ['workout' => $last?->workoutPlan, 'nutrition' => $last?->nutritionPlan],
            'renewal'      => $renewal,
            'chatUnread'   => $conversation ? $conversation->unreadFor(auth()->user()) : 0,
            // the chat opens with a ready message to send
            'renewUrl'     => !$renewal && $subscription->isRunning() && $subscription->daysLeft() <= 7
                ? route('chat.with', [$trainee, 'draft' => __(
                    'Hi :name, your subscription ends on :date. Renew from your Himma home page to keep going.',
                    ['name' => $trainee->name, 'date' => $subscription->ends_at->translatedFormat('j M')]
                )]) : null,
            'checkIns'     => $checkIns->reverse()->values(),
            'points'       => CheckInController::chartPoints($subscription, $checkIns),
            'schedule'     => $schedule,
            'lateDays'     => today()->lt($schedule['due']) ? null : (int) $schedule['due']->diffInDays(today()),
            'remindUrl'    => route('chat.with', [$trainee, 'draft' => __(
                'Hi :name, time for your weekly check-in. Send it from your Himma home page.', ['name' => $trainee->name]
            )]),
        ]);
    }
}
