<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use App\Support\WebPush;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** A phone or browser turns notifications on (or off) for the logged-in user. */
class PushSubscriptionController extends Controller
{
    // Only the browsers' own push services: the server never posts to any other address
    private const PUSH_HOSTS = ['googleapis.com', 'push.services.mozilla.com', 'push.apple.com', 'notify.windows.com'];

    public function store(Request $request)
    {
        $data = $request->validate([
            'endpoint'    => ['required', 'string', 'max:1000'],
            'keys.p256dh' => ['required', 'string', 'max:120'],
            'keys.auth'   => ['required', 'string', 'max:60'],
        ]);

        $key = WebPush::b64decode($data['keys']['p256dh']);
        if (!self::isPushService($data['endpoint']) || strlen($key) !== 65 || $key[0] !== "\x04"
            || strlen(WebPush::b64decode($data['keys']['auth'])) !== 16) {
            throw ValidationException::withMessages(['endpoint' => __('This browser can\'t receive notifications.')]);
        }

        // the same device moves to whoever is logged in on it now
        PushSubscription::updateOrCreate(['endpoint_hash' => hash('sha256', $data['endpoint'])], [
            'user_id'    => $request->user()->id,
            'endpoint'   => $data['endpoint'],
            'public_key' => $data['keys']['p256dh'],
            'auth_token' => $data['keys']['auth'],
        ]);

        return response()->noContent();
    }

    public function destroy(Request $request)
    {
        $request->validate(['endpoint' => ['required', 'string', 'max:1000']]);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint_hash', hash('sha256', $request->input('endpoint')))->delete();

        return response()->noContent();
    }

    public static function isPushService(string $endpoint): bool
    {
        $parts = parse_url($endpoint);
        if (($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['user']) || isset($parts['port'])) {
            return false;
        }
        $host = strtolower($parts['host']);
        foreach (self::PUSH_HOSTS as $allowed) {
            if ($host === $allowed || str_ends_with($host, '.' . $allowed)) {
                return true;
            }
        }

        return false;
    }
}
