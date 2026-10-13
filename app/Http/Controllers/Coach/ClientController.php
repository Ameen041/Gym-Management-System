<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
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

        return view('coach.client', compact('subscription', 'previous'));
    }
}
