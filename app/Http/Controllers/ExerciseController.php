<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    public function index(Request $request)
    {
        $muscle = $request->query('muscle');
        $search = trim((string) $request->query('q'));

        $exercises = Exercise::active()
            ->when($muscle && isset(Exercise::MUSCLE_GROUPS[$muscle]), fn ($q) => $q->where('muscle_group', $muscle))
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('equipment', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->get()
            ->sortBy(fn ($e) => array_search($e->muscle_group, array_keys(Exercise::MUSCLE_GROUPS)))
            ->values();

        $counts = Exercise::active()
            ->selectRaw('muscle_group, count(*) as total')
            ->groupBy('muscle_group')
            ->pluck('total', 'muscle_group');

        return view('exercises.index', [
            'exercises' => $exercises,
            'counts'    => $counts,
            'muscle'    => $muscle,
            'search'    => $search,
        ]);
    }

    public function show(Exercise $exercise)
    {
        abort_unless($exercise->is_active, 404);

        $related = Exercise::active()
            ->where('muscle_group', $exercise->muscle_group)
            ->whereKeyNot($exercise->id)
            ->orderBy('name')
            ->limit(4)
            ->get();

        return view('exercises.show', compact('exercise', 'related'));
    }
}
