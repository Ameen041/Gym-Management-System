<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exercise extends Model
{
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
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
     * Embeddable iframe URL for YouTube / Vimeo links, null otherwise.
     */
    public function getEmbedUrlAttribute(): ?string
    {
        $url = trim((string) $this->video_url);
        if ($url === '') {
            return null;
        }

        if (preg_match('~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?rel=0&modestbranding=1&playsinline=1';
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        return null;
    }

    /**
     * Source for a native <video> tag: uploaded file or a direct video link.
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
        return $this->embed_url !== null || $this->video_src !== null;
    }

    public function getThumbnailAttribute(): ?string
    {
        if ($this->image_path) {
            return asset($this->image_path);
        }

        if ($this->embed_url && preg_match('~youtube-nocookie\.com/embed/([A-Za-z0-9_-]{11})~', $this->embed_url, $m)) {
            return 'https://img.youtube.com/vi/' . $m[1] . '/hqdefault.jpg';
        }

        return null;
    }

    /**
     * Fallback when no video was added yet: a YouTube search for the exercise.
     */
    public function getYoutubeSearchUrlAttribute(): string
    {
        return 'https://www.youtube.com/results?search_query=' . urlencode($this->name . ' exercise proper form');
    }

    /** Same search in Arabic (videos explained in Arabic), when the Arabic name exists. */
    public function getYoutubeSearchUrlArAttribute(): ?string
    {
        return filled($this->name_ar)
            ? 'https://www.youtube.com/results?search_query=' . urlencode($this->name_ar . ' طريقة الأداء الصحيحة')
            : null;
    }

    private static function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text))));
    }
}
