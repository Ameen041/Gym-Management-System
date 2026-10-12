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
    ];

    public const DIFFICULTIES = [
        'beginner'     => 'Beginner',
        'intermediate' => 'Intermediate',
        'advanced'     => 'Advanced',
    ];

    protected $fillable = [
        'name',
        'muscle_group',
        'equipment',
        'difficulty',
        'description',
        'instructions',
        'tips',
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

    public function getMuscleLabelAttribute(): string
    {
        return self::MUSCLE_GROUPS[$this->muscle_group]['label'] ?? ucfirst($this->muscle_group);
    }

    public function getMuscleLabelArAttribute(): string
    {
        return self::MUSCLE_GROUPS[$this->muscle_group]['label_ar'] ?? '';
    }

    public function getStepsAttribute(): array
    {
        return self::lines($this->instructions);
    }

    public function getTipsListAttribute(): array
    {
        return self::lines($this->tips);
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

    private static function lines(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $text))));
    }
}
