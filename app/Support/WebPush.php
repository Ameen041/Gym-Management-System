<?php

namespace App\Support;

use App\Models\PushSubscription;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Phone / browser notifications (Web Push) without an outside service:
 * the message is encrypted for each device (RFC 8291) and signed with the
 * site's own key (VAPID, RFC 8292). The key pair is made on first use and
 * kept in the settings table, so it moves with the database backups.
 *
 *   WebPush::send($user, 'Ahmad', 'Great work this week!', '/messages/4', 'chat-4');
 *
 * Sending waits until the page has been built, at the very end of the request.
 */
class WebPush
{
    private const TTL = 86400;            // a phone that is off gets it within a day
    private const MAX_BODY = 300;         // characters shown on the notification

    // DER header of a P-256 public key, followed by the 65-byte point
    private const P256_SPKI_PREFIX = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    /** The public key the browser needs to subscribe (base64url). */
    public static function publicKey(): string
    {
        return self::keys()['public'];
    }

    public static function send(User $user, string $title, string $body, ?string $url = null, ?string $tag = null): void
    {
        $devices = PushSubscription::where('user_id', $user->id)->get();
        if ($devices->isEmpty()) {
            return;
        }

        $payload = json_encode([
            'title' => $title,
            'body'  => mb_strimwidth($body, 0, self::MAX_BODY, '…'),
            'url'   => $url,
            'tag'   => $tag,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $sent = false;
        app()->terminating(function () use ($devices, $payload, &$sent) {
            if ($sent) {
                return;   // a long-running process can call the callbacks again
            }
            $sent = true;
            foreach ($devices as $device) {
                self::deliver($device, $payload);
            }
        });
    }

    private static function deliver(PushSubscription $device, string $payload): void
    {
        try {
            $response = Http::withHeaders([
                'Authorization'    => self::authorization($device->endpoint),
                'Content-Encoding' => 'aes128gcm',
                'TTL'              => (string) self::TTL,
                'Urgency'          => 'high',
            ])->withBody(self::encrypt($payload, self::b64decode($device->public_key), self::b64decode($device->auth_token)), 'application/octet-stream')
                ->connectTimeout(3)->timeout(5)->post($device->endpoint);

            // gone (unsubscribed, app removed): forget the device
            if (in_array($response->status(), [404, 410], true)) {
                $device->delete();
            } elseif ($response->failed()) {
                report(new RuntimeException('Web Push failed: ' . $response->status() . ' ' . mb_substr($response->body(), 0, 200)));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Encrypt a message for one device (RFC 8291, "aes128gcm" content coding).
     *
     * @param  string  $uaPublic  the device key (p256dh), 65 raw bytes
     * @param  string  $authSecret  the device secret (auth), 16 raw bytes
     * @param  mixed  $local  our one-time key and $salt: new every time; given only to check the RFC example
     */
    public static function encrypt(string $payload, string $uaPublic, string $authSecret, $local = null, ?string $salt = null): string
    {
        $local ??= self::newKey();
        $asPublic = self::rawPublicKey($local);

        $shared = openssl_pkey_derive(self::publicKeyFromRaw($uaPublic), $local);
        if ($shared === false) {
            throw new RuntimeException('Web Push: bad device key');
        }

        $ikm = hash_hkdf('sha256', $shared, 32, "WebPush: info\0" . $uaPublic . $asPublic, $authSecret);
        $salt ??= random_bytes(16);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);

        // one record: the message, then the 0x02 "last record" delimiter
        $cipher = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);

        return $salt . pack('N', 4096) . chr(strlen($asPublic)) . $asPublic . $cipher . $tag;
    }

    /** "vapid t=<signed token>, k=<public key>" for the push service of this endpoint. */
    public static function authorization(string $endpoint): string
    {
        $parts = parse_url($endpoint);
        $claims = [
            'aud' => $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : ''),
            'exp' => time() + 12 * 3600,
            // who runs the site, in case the push service needs to reach us (Apple wants a real one)
            'sub' => str_starts_with((string) config('app.url'), 'https://')
                ? rtrim(config('app.url'), '/')
                : 'mailto:' . (config('mail.from.address') ?: 'admin@himma.app'),
        ];
        $signing = self::b64(json_encode(['typ' => 'JWT', 'alg' => 'ES256'])) . '.' . self::b64(json_encode($claims, JSON_UNESCAPED_SLASHES));

        $keys = self::keys();
        if (!openssl_sign($signing, $der, openssl_pkey_get_private($keys['private']), OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Web Push: cannot sign');
        }

        return 'vapid t=' . $signing . '.' . self::b64(self::derToRaw($der)) . ', k=' . $keys['public'];
    }

    /** @return array{public: string, private: string} base64url public key and PEM private key */
    private static function keys(): array
    {
        $public = Setting::get('vapid_public');
        $private = Setting::get('vapid_private');

        if (!$public || !$private) {
            $key = self::newKey();
            openssl_pkey_export($key, $private);
            $public = self::b64(self::rawPublicKey($key));
            Setting::put('vapid_private', $private);
            Setting::put('vapid_public', $public);
        }

        return ['public' => $public, 'private' => $private];
    }

    private static function newKey()
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if ($key === false) {
            throw new RuntimeException('Web Push: cannot make a key (OpenSSL)');
        }

        return $key;
    }

    /** 0x04 || x || y */
    private static function rawPublicKey($key): string
    {
        $ec = openssl_pkey_get_details($key)['ec'];

        return "\x04" . str_pad($ec['x'], 32, "\0", STR_PAD_LEFT) . str_pad($ec['y'], 32, "\0", STR_PAD_LEFT);
    }

    public static function publicKeyFromRaw(string $raw)
    {
        $pem = "-----BEGIN PUBLIC KEY-----\n"
            . chunk_split(base64_encode(hex2bin(self::P256_SPKI_PREFIX) . $raw), 64, "\n")
            . "-----END PUBLIC KEY-----\n";
        $key = openssl_pkey_get_public($pem);
        if ($key === false) {
            throw new RuntimeException('Web Push: bad public key');
        }

        return $key;
    }

    /** OpenSSL signs as DER (SEQUENCE of two INTEGERs); JWT wants r || s, 32 bytes each. */
    private static function derToRaw(string $der): string
    {
        $offset = 2;
        $raw = '';
        for ($i = 0; $i < 2; $i++) {
            $length = ord($der[$offset + 1]);
            $raw .= str_pad(ltrim(substr($der, $offset + 2, $length), "\0"), 32, "\0", STR_PAD_LEFT);
            $offset += 2 + $length;
        }

        return $raw;
    }

    public static function b64(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    public static function b64decode(string $text): string
    {
        return (string) base64_decode(strtr($text, '-_', '+/'), true);
    }
}
