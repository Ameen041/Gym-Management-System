<?php

namespace App\Console\Commands;

use App\Support\Backup;
use Illuminate\Console\Command;

class RestoreDatabase extends Command
{
    protected $signature = 'app:restore {file : Backup file name in storage/app/backups, or a full path} {--force : Do not ask for confirmation}';
    protected $description = 'Replace all data with the data of a backup (a backup of the current data is taken first)';

    public function handle(): int
    {
        $file = $this->argument('file');
        $path = Backup::path($file) ?? (is_file($file) ? $file : null);
        if (!$path) {
            $this->error("Backup not found: $file");

            return self::FAILURE;
        }

        if (!$this->option('force') && !$this->confirm('All current data will be replaced by this backup. Continue?')) {
            return self::FAILURE;
        }

        $safety = Backup::create('before-restore');
        $this->line("Current data saved first: storage/app/backups/$safety");

        $counts = Backup::restore($path);
        $this->table(['Table', 'Rows'], collect($counts)->map(fn ($n, $t) => [$t, $n])->values()->all());
        $this->info('Restore finished.');

        return self::SUCCESS;
    }
}
