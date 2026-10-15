<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Subscription;
use App\Support\Notify;
use Illuminate\Http\Request;

class AdminSubscriptionController extends Controller
{
    public const TABS = ['review', 'running', 'awaiting', 'all'];

    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), self::TABS, true) ? $request->query('tab') : 'review';
        $search = trim((string) $request->query('q'));

        $subscriptions = Subscription::with(['trainee', 'coach'])
            ->when($tab === 'review', fn ($q) => $q->where('status', 'payment_review')->oldest('payment_submitted_at'))
            ->when($tab === 'running', fn ($q) => $q->notEnded()->latest('starts_at'))
            ->when($tab === 'awaiting', fn ($q) => $q->whereIn('status', ['awaiting_payment', 'rejected'])->latest())
            ->when($tab === 'all', fn ($q) => $q->latest())
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->whereHas('trainee', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                ->orWhereHas('coach', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                ->orWhere('payment_reference', 'like', "%{$search}%")))
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'review'   => Subscription::where('status', 'payment_review')->count(),
            'running'  => Subscription::notEnded()->count(),
            'awaiting' => Subscription::whereIn('status', ['awaiting_payment', 'rejected'])->count(),
        ];

        return view('admin.subscriptions.index', compact('subscriptions', 'tab', 'search', 'counts'));
    }

    /**
     * Payment received on Sham Cash: start the subscription and split the money
     * between the coach and the platform.
     */
    public function confirm(Subscription $subscription)
    {
        abort_unless($subscription->status === 'payment_review', 403);

        $rate = Setting::commissionRate();
        $commission = round($subscription->price * $rate / 100, 2);

        // Renewal: start right after the current subscription with the same coach ends
        $currentEnd = Subscription::where('trainee_id', $subscription->trainee_id)
            ->where('coach_id', $subscription->coach_id)
            ->whereKeyNot($subscription->id)
            ->notEnded()
            ->max('ends_at');
        $startsAt = $currentEnd ? \Carbon\Carbon::parse($currentEnd) : now();

        $subscription->update([
            'status'            => 'active',
            'commission_rate'   => $rate,
            'commission_amount' => $commission,
            'coach_amount'      => round($subscription->price - $commission, 2),
            'confirmed_at'      => now(),
            'confirmed_by'      => auth()->id(),
            'starts_at'         => $startsAt,
            'ends_at'           => $startsAt->copy()->addDays($subscription->duration_days),
            'admin_note'        => null,
        ]);

        Notify::user($subscription->trainee, 'Your payment was confirmed. Your subscription with :coach is active.', [
            'coach' => $subscription->coach->name,
        ], Notify::link('trainee.dashboard'), 'fa-check-circle');

        if ($currentEnd) {
            Notify::user($subscription->coach, ':trainee renewed ":package". The new period starts on :date.', [
                'trainee' => $subscription->trainee->name,
                'package' => $subscription->package_title,
                'date'    => $startsAt->translatedFormat('j M'),
            ], Notify::link('coach.clients.show', $subscription), 'fa-rotate-right');
        } else {
            Notify::user($subscription->coach, 'New client: :trainee subscribed to ":package". Please send the plan within :days days.', [
                'trainee' => $subscription->trainee->name,
                'package' => $subscription->package_title,
                'days'    => Subscription::PLAN_DUE_DAYS,
            ], Notify::link('coach.clients.show', $subscription), 'fa-user-plus');
        }

        return back()->with('status', __('Payment confirmed. The subscription is active.'));
    }

    public function reject(Request $request, Subscription $subscription)
    {
        abort_unless($subscription->status === 'payment_review', 403);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $subscription->update(['status' => 'rejected', 'admin_note' => $data['reason']]);

        Notify::user($subscription->trainee, 'Your payment receipt was not accepted: :reason', [
            'reason' => $data['reason'],
        ], Notify::link('subscriptions.pay', $subscription), 'fa-times-circle');

        return back()->with('status', __('Payment rejected. The trainee can upload a new receipt.'));
    }
}
