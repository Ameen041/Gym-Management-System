<?php

namespace App\Http\Controllers;

use App\Models\CoachProfile;
use App\Models\User;
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
        $coach->ensureCoachProfile()->update(['status' => 'approved', 'rejection_reason' => null]);

        return back()->with('status', __('Coach approved. The profile is now visible to trainees.'));
    }

    public function reject(Request $request, User $coach)
    {
        abort_unless($coach->role === 'trainer', 404);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);

        $coach->ensureCoachProfile()->update(['status' => 'rejected', 'rejection_reason' => $data['reason']]);

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
