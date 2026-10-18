<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\AppNotification;

/**
 * Small helper to send in-app notifications.
 *
 *   Notify::user($trainee, 'Your payment was confirmed.', [], route('trainee.dashboard', [], false), 'fa-check-circle');
 *   Notify::admins('New message from :name.', ['name' => $name], ...);
 *
 * $tag avoids duplicates: with $once = false nothing is sent while an unread
 * notification with the same tag exists; with $once = true it is sent only once ever.
 * Every notification also goes to the user's phone when they turned phone notifications on ($push).
 * A failure never breaks the action that triggered the notification.
 */
class Notify
{
    public static function user(?User $user, string $message, array $params = [], ?string $url = null,
                                string $icon = 'fa-bell', ?string $tag = null, bool $once = false, bool $push = true): void
    {
        if (!$user) {
            return;
        }

        try {
            if ($tag !== null) {
                $existing = $once ? $user->notifications() : $user->unreadNotifications();
                if ($existing->where('data', 'like', '%"tag":"' . $tag . '"%')->exists()) {
                    return;
                }
            }

            $user->notify(new AppNotification($message, $params, $url, $icon, $tag));
            if ($push) {
                $locale = $user->locale ?? config('app.locale');
                WebPush::send($user, __('Himma', [], $locale), __($message, $params, $locale), $url, $tag);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function admins(string $message, array $params = [], ?string $url = null,
                                  string $icon = 'fa-bell', ?string $tag = null, bool $once = false): void
    {
        User::where('role', 'admin')->where('is_active', true)->get()
            ->each(fn (User $admin) => self::user($admin, $message, $params, $url, $icon, $tag, $once));
    }

    /** Relative URL, so links keep working behind any domain / proxy. */
    public static function link(string $route, $parameters = []): string
    {
        return route($route, $parameters, false);
    }
}
