<?php

namespace App\Http\Controllers;

use App\Models\CoachPayout;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminPayoutController extends Controller
{
    public function index()
    {
        $earned = Subscription::confirmed()->selectRaw('coach_id, SUM(coach_amount) as total')->groupBy('coach_id')->pluck('total', 'coach_id');
        $paid = CoachPayout::selectRaw('coach_id, SUM(amount) as total')->groupBy('coach_id')->pluck('total', 'coach_id');

        $coaches = User::whereIn('id', $earned->keys())
            ->orderBy('name')
            ->get()
            ->map(function (User $coach) use ($earned, $paid) {
                $coach->earned  = (float) ($earned[$coach->id] ?? 0);
                $coach->paid    = (float) ($paid[$coach->id] ?? 0);
                $coach->balance = round($coach->earned - $coach->paid, 2);
                return $coach;
            })
            ->sortByDesc('balance')
            ->values();

        $totals = [
            'sales'      => (float) Subscription::confirmed()->sum('price'),
            'commission' => (float) Subscription::confirmed()->sum('commission_amount'),
            'owed'       => $coaches->sum('balance'),
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

        CoachPayout::create($data + ['method' => 'sham_cash', 'created_by' => auth()->id()]);

        return back()->with('status', __('Payout recorded.'));
    }
}
