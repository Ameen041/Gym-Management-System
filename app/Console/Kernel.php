<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Cache;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Lets the admin System page see that the scheduler is running
        $schedule->call(fn () => Cache::forever('scheduler:heartbeat', now()->timestamp))->everyMinute();

        // Weekly check-in reminders at 10:00 Damascus time (07:00 UTC)
        $schedule->command('app:check-in-reminders')->dailyAt('07:00')->withoutOverlapping();

        // Renewal, payment and plan reminders at 10:05 Damascus time
        $schedule->command('app:subscription-reminders')->dailyAt('07:05')->withoutOverlapping();

        // Daily database backup at 03:30 Damascus time (00:30 UTC)
        $schedule->command('app:backup')->dailyAt('00:30')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
