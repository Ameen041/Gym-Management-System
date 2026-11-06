<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Pictures of an exercise uploaded by the admin (made with Gemini): saved as WebP, at most 900px,
 * with a 200px copy for the lists in thumbs/. Without GD (or WebP in it) the file is kept as it is.
 */
class ExercisePicture
{
    public const DIR = 'uploads/exercises/pictures';

    public static function store(UploadedFile $file, string $name, bool $thumb): string
    {
        File::ensureDirectoryExists(public_path(self::DIR . '/thumbs'));
        $base = $name . '-' . Str::lower(Str::random(6));

        $image = function_exists('imagewebp') ? @imagecreatefromstring((string) file_get_contents($file->getRealPath())) : false;
        if (!$image) {
            $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
            $file->move(public_path(self::DIR), $base . '.' . $ext);
            if ($thumb) {
                File::copy(public_path(self::DIR . '/' . $base . '.' . $ext), public_path(self::DIR . '/thumbs/' . $base . '.' . $ext));
            }

            return self::DIR . '/' . $base . '.' . $ext;
        }

        self::save($image, 900, public_path(self::DIR . '/' . $base . '.webp'), 84);
        if ($thumb) {
            self::save($image, 200, public_path(self::DIR . '/thumbs/' . $base . '.webp'), 80);
        }
        imagedestroy($image);

        return self::DIR . '/' . $base . '.webp';
    }

    /** Removes a picture saved by store() and its thumbnail; other paths are left alone. */
    public static function delete(?string $path): void
    {
        if ($path && str_starts_with($path, self::DIR . '/') && !str_contains($path, '..')) {
            File::delete(public_path($path));
            File::delete(public_path(self::DIR . '/thumbs/' . basename($path)));
        }
    }

    private static function save(\GdImage $image, int $max, string $path, int $quality): void
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $scale = min(1, $max / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $out = imagecreatetruecolor($nw, $nh);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255)); // a transparent PNG gets a white background
        imagecopyresampled($out, $image, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagewebp($out, $path, $quality);
        imagedestroy($out);
    }
}
