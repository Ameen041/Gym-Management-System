<?php

namespace App\Support;

/**
 * URL of a file in /public with its modification time added (?v=...), so browsers
 * and the app's offline cache always load the new version after an update.
 */
class Asset
{
    public static function url(string $path): string
    {
        $file = public_path($path);

        return asset($path) . (is_file($file) ? '?v=' . filemtime($file) : '');
    }
}
