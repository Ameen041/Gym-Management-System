<?php

namespace App\Http\Controllers;

use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Coach invite links (/c/{code}). The browser remembers the coach for a
 * while; whoever then signs up is the coach's own client, and Himma takes
 * a lower commission on their subscriptions with that coach.
 */
class InviteController extends Controller
{
    public const COOKIE = 'himma_invite';
    public const COOKIE_DAYS = 60;

    public function visit(Request $request, string $code)
    {
        $profile = self::profileFor($code);
        if (!$profile) {
            return redirect()->route('coaches.index');
        }

        // count people, not page loads
        if ($request->cookie(self::COOKIE) !== $profile->invite_code) {
            $profile->increment('invite_visits');
        }
        Cookie::queue(self::COOKIE, $profile->invite_code, self::COOKIE_DAYS * 24 * 60);

        return redirect()->route('coaches.show', $profile->user_id);
    }

    /** The coach whose link this browser came from, if they can still take clients. */
    public static function invitingCoach(Request $request): ?User
    {
        $code = $request->cookie(self::COOKIE);

        return is_string($code) ? self::profileFor($code)?->user : null;
    }

    private static function profileFor(string $code): ?CoachProfile
    {
        $profile = CoachProfile::where('invite_code', strtolower($code))->with('user')->first();
        $coach = $profile?->user;

        return $profile && $profile->isApproved() && $coach?->role === 'trainer' && $coach->is_active && !$coach->deleted_at
            ? $profile : null;
    }
}
