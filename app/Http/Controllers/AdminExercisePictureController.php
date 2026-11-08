<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Support\ExercisePicture;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Making the exercise pictures with Gemini: for each exercise, the text to paste into Gemini (start and
 * end position, the muscles in red) and two uploads for the pictures it makes.
 */
class AdminExercisePictureController extends Controller
{
    public function index(Request $request)
    {
        $show = in_array($request->query('show'), ['todo', 'done', 'all'], true) ? $request->query('show') : 'todo';
        $muscle = $request->query('muscle');
        $q = trim((string) $request->query('q'));

        $all = Exercise::orderBy('name')->get()
            ->sortBy(fn ($e) => array_search($e->muscle_group, array_keys(Exercise::MUSCLE_GROUPS)))->values();
        $done = $all->filter->has_own_media->count();

        $list = $all
            ->when($show !== 'all', fn ($c) => $c->filter(fn ($e) => $e->has_own_media === ($show === 'done')))
            ->when($muscle, fn ($c) => $c->where('muscle_group', $muscle))
            ->when($q !== '', fn ($c) => $c->filter(fn ($e) => mb_stripos($e->name . ' ' . $e->name_ar, $q) !== false))
            ->values();

        $page = max(1, (int) $request->query('page', 1));
        $exercises = new \Illuminate\Pagination\LengthAwarePaginator(
            $list->forPage($page, 15)->values(), $list->count(), 15, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.exercises.pictures', [
            'exercises' => $exercises,
            'show'      => $show,
            'muscle'    => $muscle,
            'q'         => $q,
            'done'      => $done,
            'todo'      => $all->count() - $done,
        ]);
    }

    public function store(Request $request, $id)
    {
        $exercise = Exercise::findOrFail($id);
        $first = !$exercise->has_own_pictures;

        $rules = ['image', 'mimes:jpg,jpeg,png,webp', 'max:15360'];
        $request->validate([
            'start' => [Rule::requiredIf($first || !$request->hasFile('end')), ...$rules],
            'end'   => [Rule::requiredIf($first), ...$rules],
        ], [
            'start.required' => __('Choose the start picture.'),
            'end.required'   => __('Choose the end picture.'),
            '*.image'        => __('Choose a picture (JPG, PNG or WEBP).'),
            '*.mimes'        => __('Choose a picture (JPG, PNG or WEBP).'),
        ]);

        $frames = $exercise->has_own_pictures ? array_values($exercise->frames) : [null, null];
        foreach (['start' => 0, 'end' => 1] as $field => $i) {
            if ($request->hasFile($field)) {
                ExercisePicture::delete($frames[$i] ?? null);
                $frames[$i] = ExercisePicture::store($request->file($field), $exercise->id . '-' . ($i + 1), $i === 0);
            }
        }

        $exercise->update(['frames' => array_values(array_filter($frames)), 'image_path' => null]);

        $message = __('Pictures saved for ":name".', ['name' => $exercise->display_name]);
        if ($request->expectsJson()) {
            session()->flash('status', $message);

            return response()->json(['redirect' => url()->previous()]);
        }

        return back()->with('status', $message);
    }

    /** The start and end pictures were uploaded the wrong way round. */
    public function swap($id)
    {
        $exercise = Exercise::findOrFail($id);
        abort_unless($exercise->has_own_pictures && count((array) $exercise->frames) === 2, 404);

        $exercise->update(['frames' => array_reverse(array_values($exercise->frames))]);

        return back()->with('status', __('Start and end swapped for ":name".', ['name' => $exercise->display_name]));
    }

    /** Back to the drawing (or no picture), for pictures that came out wrong. */
    public function destroy($id)
    {
        $exercise = Exercise::findOrFail($id);
        foreach ((array) $exercise->frames as $path) {
            ExercisePicture::delete($path);
        }
        $drawings = require database_path('seeders/data/exercise_frames.php');
        $exercise->update(['frames' => $drawings[$exercise->name] ?? null]);

        return back()->with('status', __('Pictures removed for ":name".', ['name' => $exercise->display_name]));
    }
}
