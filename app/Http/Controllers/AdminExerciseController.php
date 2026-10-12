<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminExerciseController extends Controller
{
    private const UPLOAD_DIR = 'uploads/exercises';

    public function index(Request $request)
    {
        $muscle = $request->query('muscle');

        $exercises = Exercise::query()
            ->when($muscle, fn ($q) => $q->where('muscle_group', $muscle))
            ->orderBy('name')
            ->get()
            ->sortBy(fn ($e) => array_search($e->muscle_group, array_keys(Exercise::MUSCLE_GROUPS)))
            ->values();

        return view('admin.exercises.index', compact('exercises', 'muscle'));
    }

    public function create()
    {
        return view('admin.exercises.create', ['exercise' => new Exercise(['is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        Exercise::create($data);

        return redirect()->route('admin.exercises.index')->with('status', __('Exercise created successfully.'));
    }

    public function edit($id)
    {
        $exercise = Exercise::findOrFail($id);
        return view('admin.exercises.edit', compact('exercise'));
    }

    public function update(Request $request, $id)
    {
        $exercise = Exercise::findOrFail($id);

        $exercise->update($this->validated($request, $exercise));

        return redirect()->route('admin.exercises.index')->with('status', __('Exercise updated successfully.'));
    }

    public function destroy($id)
    {
        $exercise = Exercise::findOrFail($id);

        $this->deleteFile($exercise->video_path);
        $this->deleteFile($exercise->image_path);
        $exercise->delete();

        return redirect()->route('admin.exercises.index')->with('status', __('Exercise deleted.'));
    }

    private function validated(Request $request, ?Exercise $exercise = null): array
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255'],
            'name_ar'      => ['nullable', 'string', 'max:255'],
            'muscle_group' => ['required', Rule::in(array_keys(Exercise::MUSCLE_GROUPS))],
            'equipment'    => ['nullable', 'string', 'max:100'],
            'equipment_ar' => ['nullable', 'string', 'max:100'],
            'difficulty'   => ['required', Rule::in(array_keys(Exercise::DIFFICULTIES))],
            'description'  => ['nullable', 'string'],
            'description_ar'  => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'instructions_ar' => ['nullable', 'string'],
            'tips'         => ['nullable', 'string'],
            'tips_ar'      => ['nullable', 'string'],
            'video_url'    => ['nullable', 'url', 'max:255'],
            'video_file'   => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:102400'],
            'image_file'   => ['nullable', 'image', 'max:4096'],
            'remove_video' => ['nullable', 'boolean'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->has('is_active');

        $videoPath = $exercise?->video_path;
        if ($request->boolean('remove_video') || $request->hasFile('video_file')) {
            $this->deleteFile($videoPath);
            $videoPath = null;
        }
        if ($request->hasFile('video_file')) {
            $videoPath = $this->upload($request->file('video_file'));
        }

        $imagePath = $exercise?->image_path;
        if ($request->boolean('remove_image') || $request->hasFile('image_file')) {
            $this->deleteFile($imagePath);
            $imagePath = null;
        }
        if ($request->hasFile('image_file')) {
            $imagePath = $this->upload($request->file('image_file'));
        }

        $data['video_path'] = $videoPath;
        $data['image_path'] = $imagePath;

        unset($data['video_file'], $data['image_file'], $data['remove_video'], $data['remove_image']);

        return $data;
    }

    private function upload(UploadedFile $file): string
    {
        $name = Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
        $file->move(public_path(self::UPLOAD_DIR), $name);

        return self::UPLOAD_DIR . '/' . $name;
    }

    private function deleteFile(?string $path): void
    {
        if ($path && str_starts_with($path, self::UPLOAD_DIR . '/')) {
            File::delete(public_path($path));
        }
    }
}
