<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Support\Notify;
use Illuminate\Console\Command;

/**
 * Once a day: remind trainees whose weekly check-in is due, and tell the
 * coach when a client is 3 days late. Each reminder is sent only once.
 */
class SendCheckInReminders extends Command
{
    protected $signature = 'app:check-in-reminders';
    protected $description = 'Remind trainees about their weekly check-in (and coaches about late ones)';

    public const COACH_AFTER_DAYS = 3;

    public function handle(): int
    {
        $sent = 0;
        Subscription::with(['trainee', 'coach'])->running()->each(function (Subscription $sub) use (&$sent) {
            $due = $sub->checkInSchedule()['due'];
            if (today()->lt($due)) {
                return;
            }
            $key = $sub->id . '-' . $due->format('Ymd');

            Notify::user($sub->trainee, 'Time for your weekly check-in with :coach.', [
                'coach' => $sub->coach->name,
            ], Notify::link('check-ins.show', $sub), 'fa-clipboard-check', 'check-in-due-' . $key, true);

            if (!today()->lt($due->copy()->addDays(self::COACH_AFTER_DAYS))) {
                Notify::user($sub->coach, ':name has not sent a check-in for :days days.', [
                    'name' => $sub->trainee->name,
                    'days' => self::COACH_AFTER_DAYS,
                ], Notify::link('coach.clients.show', $sub) . '#progress', 'fa-clock', 'check-in-late-' . $key, true);
            }
            $sent++;
        });

        $this->info("Checked: $sent subscriptions with a due check-in.");

        return self::SUCCESS;
    }
}
