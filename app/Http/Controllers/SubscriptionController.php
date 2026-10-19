<?php

namespace App\Http\Controllers;

use App\Models\CoachPackage;
use App\Models\Review;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use App\Rules\NoContactDetails;
use App\Support\Money;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Trainee side of the marketplace: choose a coach package, answer the intake
 * questions, pay with Sham Cash and upload the receipt, then review the coach.
 */
class SubscriptionController extends Controller
{
    public const GOALS = [
        'weight_loss' => 'Lose weight',
        'muscle_gain' => 'Build muscle',
        'strength'    => 'Get stronger',
        'fitness'     => 'General fitness',
        'other'       => 'Other',
    ];

    public const LEVELS = [
        'beginner'     => 'Beginner (less than 6 months)',
        'intermediate' => 'Intermediate (6 months – 2 years)',
        'advanced'     => 'Advanced (more than 2 years)',
    ];

    public function create(User $coach, CoachPackage $package)
    {
        $this->ensureBuyable($coach, $package);

        $pending = $this->pendingWith($coach);
        if ($pending && $pending->status === 'payment_review') {
            return redirect()->route('trainee.dashboard')
                ->with('error', __('Your payment for this coach is being reviewed. Please wait for the confirmation.'));
        }
        if ($pending) {
            return redirect()->route('subscriptions.pay', $pending)
                ->with('error', __('You already started a subscription with this coach. Complete or cancel it first.'));
        }

        $user = auth()->user();
        $lastIntake = $user->traineeSubscriptions()->latest()->value('intake') ?? [];
        if (is_string($lastIntake)) {
            $lastIntake = json_decode($lastIntake, true) ?: [];
        }

        return view('subscriptions.create', compact('coach', 'package', 'user', 'lastIntake'));
    }

    public function store(Request $request, User $coach, CoachPackage $package)
    {
        $this->ensureBuyable($coach, $package);

        if ($this->pendingWith($coach)) {
            return redirect()->route('trainee.dashboard');
        }

        // every health question needs a yes / no; a "yes" means: explain it and check with a doctor
        $health = (array) $request->input('health', []);
        $answered = collect(Subscription::HEALTH_QUESTIONS)->keys()
            ->every(fn ($key) => in_array((string) ($health[$key] ?? ''), ['0', '1'], true));
        $anyYes = collect(Subscription::HEALTH_QUESTIONS)->keys()->contains(fn ($key) => (string) ($health[$key] ?? '') === '1');

        $data = $request->validate([
            'goal'          => ['required', Rule::in(array_keys(self::GOALS))],
            'goal_details'  => ['nullable', 'string', 'max:1000', new NoContactDetails],
            'level'         => ['required', Rule::in(array_keys(self::LEVELS))],
            'days_per_week' => ['required', 'integer', 'min:1', 'max:7'],
            'gym'           => ['nullable', 'string', 'max:255', new NoContactDetails],
            'health'        => ['required', 'array', fn ($attribute, $value, $fail) => $answered ?: $fail(__('Answer every health question.'))],
            'health_notes'  => [$anyYes ? 'required' : 'nullable', 'string', 'max:1000', new NoContactDetails],
            'food_notes'    => ['nullable', 'string', 'max:500', new NoContactDetails],
            'doctor'        => $anyYes ? ['accepted'] : ['nullable'],
            'consent'       => ['accepted'],
            'age'           => ['required', 'integer', 'min:10', 'max:100'],
            'height'        => ['required', 'numeric', 'min:100', 'max:250'],
            'weight'        => ['required', 'numeric', 'min:30', 'max:300'],
            'phone'         => ['required', 'string', 'max:30', 'regex:/^[0-9+\s-]{8,20}$/'],
            'body_photos'   => ['nullable', 'array', 'max:4'],
            'body_photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'health.required'       => __('Answer every health question.'),
            'health_notes.required' => __('You answered "yes" to a health question: tell your coach what it is and what your doctor said.'),
            'doctor.accepted'       => __('Please confirm that you will check with your doctor before you start.'),
            'consent.accepted'      => __('Please read and accept the health notice.'),
        ]);
        $data['health'] = collect(Subscription::HEALTH_QUESTIONS)->map(fn ($q, $key) => (string) $health[$key] === '1')->all();

        $user = auth()->user();
        $user->update([
            'phone'  => $data['phone'],
            'age'    => $data['age'],
            'height' => $data['height'],
            'weight' => $data['weight'],
        ]);

        $photos = [];
        foreach ($request->file('body_photos', []) as $file) {
            $photos[] = $file->store('body_photos', Subscription::FILES_DISK);
        }

        $subscription = Subscription::create([
            'trainee_id'         => $user->id,
            'coach_id'           => $coach->id,
            'package_id'         => $package->id,
            'package_title'      => $package->title,
            'includes_workout'   => $package->includes_workout,
            'includes_nutrition' => $package->includes_nutrition,
            'duration_days'      => $package->duration_days,
            'price'              => $package->price,
            'status'             => 'awaiting_payment',
            'intake'             => collect($data)->only(['goal', 'goal_details', 'level', 'days_per_week', 'gym', 'health', 'health_notes', 'food_notes', 'age', 'height', 'weight'])->all()
                + ['health_confirmed_at' => now()->toIso8601String()],
            'body_photos'        => $photos ?: null,
        ]);

        return redirect()->route('subscriptions.pay', $subscription);
    }

    /**
     * Renew in one tap: same coach and package, the answers carried over with
     * the current weight, straight to payment. Once confirmed it starts when
     * the current subscription ends.
     */
    public function renew(Subscription $subscription)
    {
        $this->authorizeOwner($subscription);
        abort_unless($subscription->status === 'active', 404);

        $coach = $subscription->coach;
        $package = $subscription->package;
        $buyable = $package && $coach->role === 'trainer' && $coach->is_active && $coach->coachProfile?->isApproved()
            && $package->coach_id === $coach->id && $package->is_active;
        if (!$buyable) {
            return redirect()->route('coaches.show', $coach)
                ->with('error', __('This package is no longer offered. Choose one of the coach\'s packages.'));
        }

        if ($pending = $this->pendingWith($coach)) {
            return $pending->status === 'payment_review'
                ? redirect()->route('trainee.dashboard')->with('error', __('Your payment for this coach is being reviewed. Please wait for the confirmation.'))
                : redirect()->route('subscriptions.pay', $pending);
        }
        if ($subscription->renewal()) {
            return redirect()->route('trainee.dashboard')->with('success', __('You already renewed this subscription.'));
        }

        $user = auth()->user();
        $intake = array_merge($subscription->intake ?? [], array_filter(
            ['age' => $user->age, 'height' => $user->height, 'weight' => $user->weight], fn ($v) => $v !== null
        ));

        $renewal = Subscription::create([
            'trainee_id'         => $user->id,
            'coach_id'           => $coach->id,
            'package_id'         => $package->id,
            'package_title'      => $package->title,
            'includes_workout'   => $package->includes_workout,
            'includes_nutrition' => $package->includes_nutrition,
            'duration_days'      => $package->duration_days,
            'price'              => $package->price,
            'status'             => 'awaiting_payment',
            'intake'             => $intake,
        ]);

        return redirect()->route('subscriptions.pay', $renewal)
            ->with('success', __('Renewal started. Pay as before; it continues right after your current subscription.'));
    }

    public function payForm(Subscription $subscription)
    {
        $this->authorizeOwner($subscription);

        if (!in_array($subscription->status, ['awaiting_payment', 'rejected'], true)) {
            return redirect()->route('trainee.dashboard');
        }

        $subscription->load('coach');
        $payment = [
            'name'         => Setting::get('shamcash_name'),
            'account'      => Setting::get('shamcash_account'),
            'qr'           => Setting::get('shamcash_qr'),
            'instructions' => Setting::get('payment_instructions'),
        ];

        return view('subscriptions.pay', compact('subscription', 'payment'));
    }

    public function submitPayment(Request $request, Subscription $subscription)
    {
        $this->authorizeOwner($subscription);
        abort_unless(in_array($subscription->status, ['awaiting_payment', 'rejected'], true), 403);

        $request->validate([
            'payment_reference' => ['required', 'string', 'max:100'],
            'payment_proof'     => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [], ['payment_reference' => __('Transaction number'), 'payment_proof' => __('Transfer screenshot')]);

        // one transaction pays for one subscription only
        $reference = Subscription::normalizeReference($request->payment_reference);
        if (strlen($reference) < 4 || strlen($reference) > 40) {
            return back()->withErrors(['payment_reference' => __('Enter the transaction number as it is on the Sham Cash receipt.')]);
        }
        if (Subscription::where('payment_reference', $reference)->whereKeyNot($subscription->id)->exists()) {
            Notify::admins(':name tried to use a Sham Cash transaction number that was already used (:reference).', [
                'name' => $subscription->trainee->name, 'reference' => $reference,
            ], Notify::link('admin.subscriptions.index', ['q' => $reference]), 'fa-triangle-exclamation', 'reused-' . $reference);

            return back()->withErrors(['payment_reference' => __('This transaction number was already used for a subscription. Check it, or contact us if you think this is a mistake.')]);
        }

        // (the database refuses a number used twice too, if two people send it at the same moment)
        $subscription->update([
            'payment_reference'    => $reference,
            'payment_proof'        => $request->file('payment_proof')->store('payments', Subscription::FILES_DISK),
            'status'               => 'payment_review',
            'payment_submitted_at' => now(),
            'admin_note'           => null,
        ]);

        Notify::admins('New payment receipt from :trainee: :amount for coach :coach.', [
            'trainee' => $subscription->trainee->name,
            'amount'  => Money::format($subscription->price),
            'coach'   => $subscription->coach->name,
        ], Notify::link('admin.subscriptions.index', ['tab' => 'review']), 'fa-receipt');

        return redirect()->route('trainee.dashboard')
            ->with('success', __('Thank you! Your payment is being reviewed. Your subscription starts as soon as it is confirmed.'));
    }

    public function cancel(Subscription $subscription)
    {
        $this->authorizeOwner($subscription);
        abort_unless(in_array($subscription->status, ['awaiting_payment', 'rejected'], true), 403);

        $subscription->update(['status' => 'cancelled']);

        return redirect()->route('trainee.dashboard')->with('success', __('The subscription was cancelled.'));
    }

    public function review(Request $request, Subscription $subscription)
    {
        $this->authorizeOwner($subscription);
        abort_unless($subscription->status === 'active', 403);

        if ($subscription->review()->exists()) {
            return back()->with('error', __('You already reviewed this subscription.'));
        }

        $data = $request->validate([
            'rating'  => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000', new NoContactDetails],
        ]);

        Review::create($data + [
            'subscription_id' => $subscription->id,
            'trainee_id'      => $subscription->trainee_id,
            'coach_id'        => $subscription->coach_id,
        ]);

        $subscription->coach->ensureCoachProfile()->refreshRating();

        Notify::user($subscription->coach, ':trainee rated you :rating/5.', [
            'trainee' => $subscription->trainee->name,
            'rating'  => $data['rating'],
        ], Notify::link('coaches.show', $subscription->coach_id) . '#reviews', 'fa-star');

        return back()->with('success', __('Thank you for your review!'));
    }

    private function ensureBuyable(User $coach, CoachPackage $package): void
    {
        abort_unless(
            $coach->role === 'trainer'
            && $coach->is_active
            && $coach->coachProfile?->isApproved()
            && $package->coach_id === $coach->id
            && $package->is_active,
            404
        );
    }

    private function pendingWith(User $coach): ?Subscription
    {
        return Subscription::where('trainee_id', auth()->id())
            ->where('coach_id', $coach->id)
            ->whereIn('status', ['awaiting_payment', 'payment_review', 'rejected'])
            ->latest()
            ->first();
    }

    private function authorizeOwner(Subscription $subscription): void
    {
        abort_unless($subscription->trainee_id === auth()->id(), 404);
    }
}
