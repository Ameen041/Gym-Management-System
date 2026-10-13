<?php

namespace App\Http\Controllers;

use App\Models\CoachProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

class CoachDirectoryController extends Controller
{
    public const SORTS = [
        'recommended' => 'Recommended',
        'rating'      => 'Top rated',
        'price_asc'   => 'Lowest price',
        'price_desc'  => 'Highest price',
        'newest'      => 'Newest',
    ];

    public function index(Request $request)
    {
        $filters = [
            'specialty' => $request->query('specialty'),
            'city'      => $request->query('city'),
            'gender'    => $request->query('gender'),
            'max_price' => $request->query('max_price'),
            'q'         => trim((string) $request->query('q')),
            'sort'      => array_key_exists($request->query('sort'), self::SORTS) ? $request->query('sort') : 'recommended',
        ];

        // Few hundred coaches at most: filtering in PHP keeps it database-agnostic
        $coaches = self::listedCoaches()
            ->filter(function (User $coach) use ($filters) {
                $profile = $coach->coachProfile;
                if ($filters['specialty'] && !in_array($filters['specialty'], (array) $profile->specialties, true)) return false;
                if ($filters['city'] && $profile->city !== $filters['city']) return false;
                if ($filters['gender'] && $profile->gender !== $filters['gender']) return false;
                if (is_numeric($filters['max_price']) && $coach->min_price > (float) $filters['max_price']) return false;
                if ($filters['q'] !== '' && mb_stripos($coach->name, $filters['q']) === false) return false;
                return true;
            });

        $coaches = match ($filters['sort']) {
            'rating'     => $coaches->sortByDesc(fn ($c) => [$c->coachProfile->rating_avg, $c->coachProfile->rating_count]),
            'price_asc'  => $coaches->sortBy('min_price'),
            'price_desc' => $coaches->sortByDesc('min_price'),
            'newest'     => $coaches->sortByDesc('created_at'),
            default      => $coaches->sortByDesc(fn ($c) => [(int) $c->coachProfile->is_featured, $c->coachProfile->rating_avg, $c->coachProfile->rating_count]),
        };

        return view('coaches.index', [
            'coaches' => $coaches->values(),
            'filters' => $filters,
        ]);
    }

    public function show(User $coach)
    {
        $viewer = auth()->user();
        $profile = $coach->coachProfile;

        $isListed = $coach->role === 'trainer' && $coach->is_active && $profile?->isApproved();
        $canPreview = $viewer && ($viewer->id === $coach->id || $viewer->role === 'admin');
        abort_unless($isListed || ($canPreview && $coach->role === 'trainer'), 404);

        $profile = $coach->ensureCoachProfile();
        $packages = $coach->packages()->where('is_active', true)->orderBy('price')->get();
        $reviews = Review::with('trainee:id,name')
            ->where('coach_id', $coach->id)
            ->latest()
            ->limit(20)
            ->get();
        $clientsCount = $coach->coachSubscriptions()->confirmed()->distinct('trainee_id')->count('trainee_id');

        return view('coaches.show', compact('coach', 'profile', 'packages', 'reviews', 'clientsCount', 'isListed'));
    }

    /**
     * Coaches shown to trainees: active trainer accounts with an approved
     * profile and at least one active package.
     */
    public static function listedCoaches()
    {
        return User::query()
            ->where('role', 'trainer')
            ->where('is_active', true)
            ->whereHas('coachProfile', fn ($q) => $q->where('status', 'approved'))
            ->whereHas('packages', fn ($q) => $q->where('is_active', true))
            ->with('coachProfile')
            ->withMin(['packages as min_price' => fn ($q) => $q->where('is_active', true)], 'price')
            ->get();
    }
}
