<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Support\ExercisePicture;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminExerciseController extends Controller
{
    private const UPLOAD_DIR = 'uploads/exercises';

    // phone videos: MP4 (Android) or MOV (iPhone), up to 200 MB
    private const VIDEO_RULES = ['required', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:204800'];

    public function index(Request $request)
    {
        $muscle = $request->query('muscle');
        $missingVideo = $request->boolean('missing_video');
        $missingPicture = $request->boolean('missing_picture');

        $exercises = Exercise::query()
            ->when($muscle, fn ($q) => $q->where('muscle_group', $muscle))
            ->when($missingVideo, fn ($q) => $q->whereNull('video_path'))
            ->when($missingPicture, fn ($q) => $q->whereNull('image_path')->whereNull('frames'))
            ->orderBy('name')
            ->get()
            ->sortBy(fn ($e) => array_search($e->muscle_group, array_keys(Exercise::MUSCLE_GROUPS)))
            ->values();

        $withoutVideo = Exercise::whereNull('video_path')->count();
        $withoutPicture = Exercise::whereNull('image_path')->whereNull('frames')->count();

        return view('admin.exercises.index', compact('exercises', 'muscle', 'missingVideo', 'missingPicture', 'withoutVideo', 'withoutPicture'));
    }

    /** Upload (or replace) an exercise's video from the list. */
    public function updateVideo(Request $request, $id)
    {
        $exercise = Exercise::findOrFail($id);
        $request->validate([
            'video_file' => self::VIDEO_RULES,
            'poster'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ], self::videoMessages(), ['video_file' => __('Video')]);

        $this->deleteFile($exercise->video_path);
        $data = ['video_path' => $this->upload($request->file('video_file')), 'video_url' => null];

        // the browser takes a frame of the video as its cover: it becomes the exercise's picture, unless the
        // admin uploaded pictures of their own (a cover from an earlier video is replaced)
        $frames = array_values(array_filter((array) $exercise->frames));
        $isCover = count($frames) === 1 && str_contains(basename($frames[0]), '-cover-');
        if ($request->hasFile('poster') && (!$exercise->has_own_pictures || $isCover)) {
            if ($isCover) {
                ExercisePicture::delete($frames[0]);
            }
            $data['frames'] = [ExercisePicture::store($request->file('poster'), $exercise->id . '-cover', true)];
            $data['image_path'] = null;
        }
        $exercise->update($data);

        return $this->done($request, back()->getTargetUrl(), __('Video saved for ":name".', ['name' => $exercise->display_name]));
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

        return $this->done($request, route('admin.exercises.index'), __('Exercise created successfully.'));
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

        return $this->done($request, route('admin.exercises.index'), __('Exercise updated successfully.'));
    }

    public function destroy($id)
    {
        $exercise = Exercise::findOrFail($id);

        $this->deleteFile($exercise->video_path);
        $this->deleteFile($exercise->image_path);
        $exercise->delete();

        return redirect()->route('admin.exercises.index')->with('status', __('Exercise deleted.'));
    }

    private static function videoMessages(): array
    {
        return [
            'video_file.mimetypes' => __('Choose a video file: MP4, or MOV from an iPhone.'),
            'video_file.max'       => __('The video is bigger than 200 MB. Film a shorter video, or at a lower quality (720p).'),
            // usually the server's own upload limit (php.ini), which the System page checks
            'video_file.uploaded'  => __('The video is bigger than the server accepts (:size MB). Raise the upload limit (see Admin → System), or use a smaller video.', ['size' => round(\Illuminate\Http\UploadedFile::getMaxFilesize() / 1048576, 1)]),
        ];
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
            'video_file'   => array_merge(['nullable'], array_slice(self::VIDEO_RULES, 1)),
            'image_file'   => ['nullable', 'image', 'max:4096'],
            'primary_muscles'     => ['nullable', 'array'],
            'primary_muscles.*'   => [Rule::in(array_keys(Exercise::MUSCLES))],
            'secondary_muscles'   => ['nullable', 'array'],
            'secondary_muscles.*' => [Rule::in(array_keys(Exercise::MUSCLES))],
            'remove_video' => ['nullable', 'boolean'],
            'remove_image' => ['nullable', 'boolean'],
        ], self::videoMessages());

        $data['is_active'] = $request->has('is_active');
        $data['primary_muscles'] = array_values($data['primary_muscles'] ?? []) ?: null;
        $data['secondary_muscles'] = array_values(array_diff($data['secondary_muscles'] ?? [], $data['primary_muscles'] ?? [])) ?: null;

        $videoPath = $exercise?->video_path;
        if ($request->boolean('remove_video') || $request->hasFile('video_file')) {
            $this->deleteFile($videoPath);
            $videoPath = null;
        }
        if ($request->hasFile('video_file')) {
            $videoPath = $this->upload($request->file('video_file'));
            $data['video_url'] = null;
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

    /**
     * Forms with big videos are sent by app.js with a progress bar and get JSON back;
     * the message is kept for the page it goes to next.
     */
    private function done(Request $request, string $to, string $message)
    {
        if ($request->expectsJson()) {
            session()->flash('status', $message);

            return response()->json(['redirect' => $to]);
        }

        return redirect($to)->with('status', $message);
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
