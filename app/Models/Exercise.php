<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
    /** Names of the exercises the admin deleted, so the starter list run on every update doesn't add them back. */
    public static function deletedNames(): array
    {
        return json_decode((string) Setting::get('deleted_exercises', '[]'), true) ?: [];
    }

    public const MUSCLE_GROUPS = [
        'chest'     => ['label' => 'Chest',     'label_ar' => 'الصدر'],
        'back'      => ['label' => 'Back',      'label_ar' => 'الظهر'],
        'shoulders' => ['label' => 'Shoulders', 'label_ar' => 'الأكتاف'],
        'biceps'    => ['label' => 'Biceps',    'label_ar' => 'البايسبس'],
        'triceps'   => ['label' => 'Triceps',   'label_ar' => 'الترايسبس'],
        'legs'      => ['label' => 'Legs',      'label_ar' => 'الأرجل'],
        'glutes'    => ['label' => 'Glutes',    'label_ar' => 'المؤخرة'],
        'abs'       => ['label' => 'Abs',       'label_ar' => 'البطن'],
        'forearms'  => ['label' => 'Forearms',  'label_ar' => 'الساعد'],
        'full_body' => ['label' => 'Full Body', 'label_ar' => 'الجسم كامل'],
        'cardio'    => ['label' => 'Cardio',    'label_ar' => 'الكارديو'],
    ];

    /** Equipment families used by the filters (derived from the free-text equipment). */
    public const EQUIPMENT_TYPES = [
        'barbell'    => 'Barbell',
        'dumbbell'   => 'Dumbbells',
        'cable'      => 'Cable',
        'machine'    => 'Machine',
        'bodyweight' => 'Bodyweight',
        'kettlebell' => 'Kettlebell',
        'other'      => 'Other',
    ];

    public const DIFFICULTIES = [
        'beginner'     => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced'     => 'Advanced',
    ];

    /** Muscles shown on the muscle map (partials/muscle-map): key => [English, Arabic]. */
    public const MUSCLES = [
        'chest'       => ['Chest', 'الصدر'],
        'shoulders'   => ['Shoulders', 'الأكتاف'],
        'traps'       => ['Traps', 'الترابيس'],
        'lats'        => ['Lats', 'المجنص (الظهر العريض)'],
        'middle_back' => ['Middle back', 'منتصف الظهر'],
        'lower_back'  => ['Lower back', 'أسفل الظهر'],
        'biceps'      => ['Biceps', 'البايسبس'],
        'triceps'     => ['Triceps', 'الترايسبس'],
        'forearms'    => ['Forearms', 'الساعد'],
        'abs'         => ['Abs', 'البطن'],
        'obliques'    => ['Obliques', 'الخواصر'],
        'glutes'      => ['Glutes', 'المؤخرة'],
        'quads'       => ['Quads', 'الفخذ الأمامي'],
        'hamstrings'  => ['Hamstrings', 'الفخذ الخلفي'],
        'calves'      => ['Calves', 'السمانة'],
        'adductors'   => ['Inner thighs', 'الفخذ الداخلي'],
        'abductors'   => ['Outer hips', 'الورك الخارجي'],
    ];

    /** Main muscles of a muscle group, for exercises that don't list their own. */
    private const GROUP_MUSCLES = [
        'chest' => ['chest'], 'back' => ['lats', 'middle_back'], 'shoulders' => ['shoulders'], 'biceps' => ['biceps'],
        'triceps' => ['triceps'], 'legs' => ['quads', 'hamstrings'], 'glutes' => ['glutes'], 'abs' => ['abs'],
        'forearms' => ['forearms'], 'full_body' => ['quads', 'glutes', 'shoulders'], 'cardio' => ['quads', 'calves'],
    ];

    // Fields that have an Arabic version stored in "{field}_ar"
    public const TRANSLATABLE = ['name', 'equipment', 'description', 'instructions', 'tips'];

    protected $fillable = [
        'name',
        'name_ar',
        'muscle_group',
        'equipment',
        'equipment_ar',
        'difficulty',
        'description',
        'description_ar',
        'instructions',
        'instructions_ar',
        'tips',
        'tips_ar',
        'video_url',
        'video_path',
        'image_path',
        'frames',
        'primary_muscles',
        'secondary_muscles',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active'         => 'boolean',
        'frames'            => 'array',
        'primary_muscles'   => 'array',
        'secondary_muscles' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function muscleLabel(string $key): string
    {
        $group = self::MUSCLE_GROUPS[$key] ?? null;
        if (!$group) {
            return ucfirst($key);
        }

        return app()->isLocale('ar') ? $group['label_ar'] : $group['label'];
    }

    /**
     * Translate a free-text muscle name used in workout plans ("Chest", "Back"...).
     * Unknown names are returned unchanged.
     */
    public static function translateMuscleName(?string $name): string
    {
        $key = str_replace(' ', '_', strtolower(trim((string) $name)));

        return isset(self::MUSCLE_GROUPS[$key]) ? self::muscleLabel($key) : (string) $name;
    }

    /**
     * Value of a translatable field in the current language,
     * falling back to English when no Arabic text was added.
     */
    public function localized(string $field): ?string
    {
        if (app()->isLocale('ar') && filled($this->{$field . '_ar'})) {
            return $this->{$field . '_ar'};
        }

        return $this->{$field};
    }

    public function getDisplayNameAttribute(): string
    {
        return (string) $this->localized('name');
    }

    public function getDisplayEquipmentAttribute(): ?string
    {
        return $this->localized('equipment');
    }

    public function getDisplayDescriptionAttribute(): ?string
    {
        return $this->localized('description');
    }

    public function getEquipmentTypeAttribute(): string
    {
        $equipment = strtolower((string) $this->equipment);

        return match (true) {
            str_contains($equipment, 'smith') || str_contains($equipment, 'machine') || str_contains($equipment, 'treadmill')
                || str_contains($equipment, 'bike') || str_contains($equipment, 'elliptical') => str_contains($equipment, 'cable') ? 'cable' : 'machine',
            str_contains($equipment, 'cable')      => 'cable',
            str_contains($equipment, 'kettlebell') => 'kettlebell',
            str_contains($equipment, 'dumbbell')   => 'dumbbell',
            str_contains($equipment, 'barbell') || str_contains($equipment, 'ez bar') => 'barbell',
            $equipment === '' || str_contains($equipment, 'bodyweight') || str_contains($equipment, 'pull-up')
                || str_contains($equipment, 'dip') || $equipment === 'bench' || str_contains($equipment, 'flat bench') => 'bodyweight',
            default => 'other',
        };
    }

    public function getMuscleLabelAttribute(): string
    {
        return self::muscleLabel((string) $this->muscle_group);
    }

    public function getDifficultyLabelAttribute(): string
    {
        return __(self::DIFFICULTIES[$this->difficulty] ?? (string) $this->difficulty);
    }

    public function getStepsAttribute(): array
    {
        return self::lines($this->localized('instructions'));
    }

    public function getTipsListAttribute(): array
    {
        return self::lines($this->localized('tips'));
    }

    /**
     * Source for the <video> tag: a video uploaded to Himma, or a direct link to a video file.
     * YouTube and other sites are not shown: the videos are Himma's own.
     */
    public function getVideoSrcAttribute(): ?string
    {
        if ($this->video_path) {
            return asset($this->video_path);
        }

        $url = trim((string) $this->video_url);
        if ($url !== '' && preg_match('~\.(mp4|webm|ogg|mov)(\?.*)?$~i', $url)) {
            return $url;
        }

        return null;
    }

    public function getHasVideoAttribute(): bool
    {
        return $this->video_src !== null;
    }

    /** The drawings of the start and end position, in order (none when the admin uploaded a picture instead). */
    public function getFrameUrlsAttribute(): array
    {
        if ($this->image_path) {
            return [asset($this->image_path)];
        }

        return array_map(fn ($path) => asset($path), array_values(array_filter((array) $this->frames)));
    }

    /** The Everkinetic drawings need their credit under them (CC BY-SA); Himma's own pictures don't. */
    public function getNeedsDrawingCreditAttribute(): bool
    {
        if ($this->image_path) {
            return false;
        }

        foreach ((array) $this->frames as $path) {
            if (str_starts_with((string) $path, 'images/exercises/') && !str_starts_with((string) $path, 'images/exercises/ai/')) {
                return true;
            }
        }

        return false;
    }

    /** True when the picture is one of the line drawings (shown dark on dark screens). */
    public function getIsDrawingAttribute(): bool
    {
        return !$this->image_path && !empty($this->frames);
    }

    /** Small picture for lists: the picture's own thumbnail (thumbs/) when there is one. */
    public function getThumbnailAttribute(): ?string
    {
        $first = array_values(array_filter((array) $this->frames))[0] ?? null;
        if (!$this->image_path && $first) {
            $thumb = str_starts_with($first, \App\Support\ExercisePicture::DIR . '/')
                ? \App\Support\ExercisePicture::DIR . '/thumbs/' . basename($first)
                : preg_replace('~^images/exercises/~', 'images/exercises/thumbs/', $first);
            if ($thumb !== $first && is_file(public_path($thumb))) {
                return asset($thumb);
            }
        }

        return $this->frame_urls[0] ?? null;
    }

    /** The exercise has its own video or its own pictures (what the "Exercise videos" page counts as done). */
    public function getHasOwnMediaAttribute(): bool
    {
        return $this->has_video || $this->has_own_pictures;
    }

    /** Pictures made for Himma (Gemini), uploaded by the admin or shipped in images/exercises/ai. */
    public function getHasOwnPicturesAttribute(): bool
    {
        $first = (string) (array_values(array_filter((array) $this->frames))[0] ?? '');

        return !$this->image_path && (str_starts_with($first, 'images/exercises/ai/') || str_starts_with($first, \App\Support\ExercisePicture::DIR . '/'));
    }

    /** Muscle keys for the muscle map: ['primary' => [...], 'secondary' => [...]]. */
    public function getMusclesAttribute(): array
    {
        $primary = array_values(array_intersect((array) $this->primary_muscles, array_keys(self::MUSCLES)));
        if (!$primary) {
            $primary = self::GROUP_MUSCLES[$this->muscle_group] ?? [];
        }
        $secondary = array_values(array_diff(array_intersect((array) $this->secondary_muscles, array_keys(self::MUSCLES)), $primary));

        return ['primary' => $primary, 'secondary' => $secondary];
    }

    public static function muscleName(string $key): string
    {
        $names = self::MUSCLES[$key] ?? [ucfirst(str_replace('_', ' ', $key)), null];

        return app()->isLocale('ar') && $names[1] ? $names[1] : $names[0];
    }

    private static function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text))));
    }
}
