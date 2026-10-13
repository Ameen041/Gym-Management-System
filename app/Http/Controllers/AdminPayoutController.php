<?php

namespace App\Http\Controllers;

use App\Models\CoachPayout;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Money;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPayoutController extends Controller
{
    public function index()
    {
        $coachIds = Subscription::confirmed()->distinct()->pluck('coach_id');

        $coaches = User::whereIn('id', $coachIds)
            ->orderBy('name')
            ->get()
            ->each(fn (User $coach) => $coach->earnings = $coach->coachEarnings())
            ->sortByDesc(fn (User $coach) => [$coach->earnings['available'], $coach->earnings['balance']])
            ->values();

        $totals = [
            'sales'      => (float) Subscription::confirmed()->sum('price'),
            'commission' => (float) Subscription::confirmed()->sum('commission_amount'),
            'owed'       => $coaches->sum(fn ($c) => $c->earnings['balance']),
            'available'  => $coaches->sum(fn ($c) => $c->earnings['available']),
        ];

        $payouts = CoachPayout::with('coach')->latest()->limit(30)->get();

        return view('admin.payouts.index', compact('coaches', 'payouts', 'totals'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'coach_id'  => ['required', Rule::exists('users', 'id')->where('role', 'trainer')],
            'amount'    => ['required', 'numeric', 'min:0.01', 'max:100000'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note'      => ['nullable', 'string', 'max:255'],
        ]);

        $payout = CoachPayout::create($data + ['method' => 'sham_cash', 'created_by' => auth()->id()]);

        Notify::user($payout->coach, 'A payout of :amount was sent to you on Sham Cash.', [
            'amount' => Money::format($payout->amount),
        ], Notify::link('trainer.dashboard'), 'fa-hand-holding-usd');

        return back()->with('status', __('Payout recorded.'));
    }
}
