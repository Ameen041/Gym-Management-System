<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Support\Money;
use App\Support\Notify;
use Illuminate\Console\Command;

/**
 * Once a day, each reminder sent only once:
 *  - trainee: subscription ends in 3 days / has ended (without a renewal), payment not finished after 2 days
 *  - coach:   plan due tomorrow, plan late, a client's subscription ends in 3 days
 *  - admin:   receipt waiting more than a day, plan late
 */
class SendSubscriptionReminders extends Command
{
    protected $signature = 'app:subscription-reminders';
    protected $description = 'Renewal, payment and plan reminders for trainees, coaches and the admin';

    public const RENEW_DAYS = 3;
    public const UNPAID_DAYS = 2;
    public const REVIEW_HOURS = 24;

    private int $sent = 0;

    public function handle(): int
    {
        $this->endingAndEnded();
        $this->plans();
        $this->payments();

        $this->info("Reminders checked ({$this->sent} subscriptions concerned).");

        return self::SUCCESS;
    }

    private function endingAndEnded(): void
    {
        Subscription::with(['trainee', 'coach'])->confirmed()
            ->where('ends_at', '>', now()->subDays(3))
            ->where('ends_at', '<=', now()->addDays(self::RENEW_DAYS))
            ->each(function (Subscription $sub) {
                if ($sub->isUpcoming() || $sub->renewal()) {
                    return;
                }
                $this->sent++;

                if ($sub->ends_at->isFuture()) {
                    Notify::user($sub->trainee, 'Your subscription with :coach ends on :date. Renew it in one tap to keep your plan and check-ins going.', [
                        'coach' => $sub->coach->name, 'date' => $sub->ends_at->translatedFormat('j M'),
                    ], Notify::link('trainee.dashboard'), 'fa-rotate-right', 'renew-' . $sub->id, true);

                    Notify::user($sub->coach, ':name\'s subscription ends on :date. A short message from you may help them renew.', [
                        'name' => $sub->trainee->name, 'date' => $sub->ends_at->translatedFormat('j M'),
                    ], Notify::link('coach.clients.show', $sub), 'fa-hourglass-end', 'coach-renew-' . $sub->id, true);
                } else {
                    Notify::user($sub->trainee, 'Your subscription with :coach has ended. Renew to continue where you left off.', [
                        'coach' => $sub->coach->name,
                    ], Notify::link('trainee.dashboard'), 'fa-rotate-right', 'ended-' . $sub->id, true);
                }
            });
    }

    private function plans(): void
    {
        Subscription::with(['trainee', 'coach', 'workoutPlan', 'nutritionPlan'])->running()
            ->where('starts_at', '<=', now()->addDay()->subDays(Subscription::PLAN_DUE_DAYS))
            ->each(function (Subscription $sub) {
                if (!$sub->missingPlans()) {
                    return;
                }
                $this->sent++;
                $params = ['name' => $sub->trainee->name, 'date' => $sub->plan_due_at->translatedFormat('j M')];
                $link = Notify::link('coach.clients.show', $sub);

                if ($sub->plan_due_at->isFuture()) {
                    Notify::user($sub->coach, 'The plan for :name is due by :date.', $params, $link, 'fa-hourglass-half', 'plan-due-' . $sub->id, true);
                } else {
                    Notify::user($sub->coach, 'The plan for :name is late: it was due on :date. Please send it today.', $params, $link, 'fa-triangle-exclamation', 'plan-late-' . $sub->id, true);
                    Notify::admins('Late plan: :coach has not sent the plan for :name (due :date).', $params + ['coach' => $sub->coach->name],
                        Notify::link('admin.coaches.show', $sub->coach_id), 'fa-triangle-exclamation', 'plan-late-admin-' . $sub->id, true);
                }
            });
    }

    private function payments(): void
    {
        Subscription::with(['trainee', 'coach'])->where('status', 'awaiting_payment')
            ->where('created_at', '<=', now()->subDays(self::UNPAID_DAYS))
            ->where('created_at', '>', now()->subDays(30))
            ->each(function (Subscription $sub) {
                $this->sent++;
                Notify::user($sub->trainee, 'Your subscription with :coach is waiting for payment. Pay :amount on Sham Cash and upload the receipt to start.', [
                    'coach' => $sub->coach->name, 'amount' => Money::format($sub->price),
                ], Notify::link('subscriptions.pay', $sub), 'fa-wallet', 'pay-' . $sub->id, true);
            });

        Subscription::with(['trainee', 'coach'])->where('status', 'payment_review')
            ->where('payment_submitted_at', '<=', now()->subHours(self::REVIEW_HOURS))
            ->each(function (Subscription $sub) {
                $this->sent++;
                Notify::admins('A receipt from :trainee (:amount for :coach) has been waiting for review for over a day.', [
                    'trainee' => $sub->trainee->name, 'amount' => Money::format($sub->price), 'coach' => $sub->coach->name,
                ], Notify::link('admin.subscriptions.index', ['tab' => 'review']), 'fa-receipt', 'review-late-' . $sub->id, true);
            });
    }
}
