<?php

namespace App\Http\Controllers;

use App\Models\CoachProfile;
use App\Models\User;
use App\Support\Notify;
use Illuminate\Http\Request;

class AdminCoachController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['pending', 'approved', 'rejected', 'all'], true)
            ? $request->query('status') : 'pending';

        $coaches = User::where('role', 'trainer')
            ->with('coachProfile')
            ->withCount(['coachSubscriptions as clients_count' => fn ($q) => $q->running()])
            ->when($status !== 'all', fn ($q) => $status === 'pending'
                // coaches who never opened their profile page have no profile row yet
                ? $q->where(fn ($q) => $q->whereDoesntHave('coachProfile')->orWhereHas('coachProfile', fn ($p) => $p->where('status', 'pending')))
                : $q->whereHas('coachProfile', fn ($p) => $p->where('status', $status)))
            ->latest()
            ->get();

        $counts = CoachProfile::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.coaches.index', compact('coaches', 'status', 'counts'));
    }

    public function show(User $coach)
    {
        abort_unless($coach->role === 'trainer', 404);

        $profile = $coach->ensureCoachProfile();
        $packages = $coach->packages()->orderBy('price')->get();
        $subscriptions = $coach->coachSubscriptions()->with('trainee')->latest()->limit(20)->get();

        return view('admin.coaches.show', [
            'coach'         => $coach,
            'profile'       => $profile,
            'packages'      => $packages,
            'subscriptions' => $subscriptions,
            'missing'       => $profile->missingSteps(),
            'earnings'      => $coach->coachEarnings(),
        ]);
    }

    public function approve(User $coach)
    {
        abort_unless($coach->role === 'trainer', 404);
        $profile = $coach->ensureCoachProfile();
        $profile->update(['status' => 'approved', 'rejection_reason' => null]);

        // The coaches page lists approved coaches with at least one active package:
        // say plainly what is still missing instead of "you are visible now"
        $missing = $profile->missingSteps();
        if ($missing) {
            Notify::user($coach, 'Your coach profile was approved. To appear on the coaches page, complete: :missing', [
                'missing' => implode('، ', $missing),
            ], Notify::link('trainer.dashboard'), 'fa-check-circle');

            return back()->with('status', __('Coach approved. They will appear on the coaches page once they complete: :missing', [
                'missing' => implode('، ', $missing),
            ]));
        }

        Notify::user($coach, 'Your coach profile was approved. Trainees can now find you and subscribe.', [],
            Notify::link('coaches.show', $coach), 'fa-check-circle');

        return back()->with('status', __('Coach approved. The profile is now visible to trainees.'));
    }

    public function reject(Request $request, User $coach)
    {
        abort_unless($coach->role === 'trainer', 404);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $coach->ensureCoachProfile()->update(['status' => 'rejected', 'rejection_reason' => $data['reason']]);

        Notify::user($coach, 'Your coach profile needs changes: :reason', ['reason' => $data['reason']],
            Notify::link('coach.profile.edit'), 'fa-exclamation-circle');

        return back()->with('status', __('Coach profile rejected.'));
    }

    public function toggleFeatured(User $coach)
    {
        abort_unless($coach->role === 'trainer', 404);
        $profile = $coach->ensureCoachProfile();
        $profile->update(['is_featured' => !$profile->is_featured]);

        return back()->with('status', $profile->is_featured ? __('Coach is now featured.') : __('Coach is no longer featured.'));
    }
}
