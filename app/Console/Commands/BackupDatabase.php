<?php

namespace App\Console\Commands;

use App\Support\Backup;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'app:backup';
    protected $description = 'Back up the database to storage/app/backups (keeps the last 14 daily backups)';

    public function handle(): int
    {
        $name = Backup::create();
        Backup::prune();
        $this->info("Backup written: storage/app/backups/$name");

        return self::SUCCESS;
    }
}
