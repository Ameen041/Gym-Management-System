<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

/**
 * Body photos and payment receipts used to be saved under storage/app/public,
 * which anyone with the link can open. Move them to the private disk; the
 * paths stored in the database stay the same.
 */
return new class extends Migration
{
    private const FOLDERS = ['body_photos', 'payments'];

    public function up(): void
    {
        $this->move(Storage::disk('public'), Storage::disk('local'));
    }

    public function down(): void
    {
        $this->move(Storage::disk('local'), Storage::disk('public'));
    }

    private function move($from, $to): void
    {
        foreach (self::FOLDERS as $folder) {
            foreach ($from->allFiles($folder) as $path) {
                if (!$to->exists($path)) {
                    $stream = $from->readStream($path);
                    $to->writeStream($path, $stream);
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
                $from->delete($path);
            }
            $from->deleteDirectory($folder);
        }
    }
};
