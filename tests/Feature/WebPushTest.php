<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\PushSubscription;
use App\Models\Subscription;
use App\Models\User;
use App\Support\Notify;
use App\Support\WebPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebPushTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = 'https://fcm.googleapis.com/fcm/send/abc123';

    /** A browser's key pair and secret, like the ones it makes when you allow notifications. */
    private function device(): array
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $ec = openssl_pkey_get_details($key)['ec'];

        return [
            'private' => $key,
            'public'  => "\x04" . str_pad($ec['x'], 32, "\0", STR_PAD_LEFT) . str_pad($ec['y'], 32, "\0", STR_PAD_LEFT),
            'auth'    => random_bytes(16),
        ];
    }

    /** What the browser does with a push message (RFC 8291), to check the server's side. */
    private function decrypt(string $body, array $device): string
    {
        $salt = substr($body, 0, 16);
        $idLength = ord($body[20]);
        $serverPublic = substr($body, 21, $idLength);
        $cipher = substr($body, 21 + $idLength);

        $shared = openssl_pkey_derive(WebPush::publicKeyFromRaw($serverPublic), $device['private']);
        $ikm = hash_hkdf('sha256', $shared, 32, "WebPush: info\0" . $device['public'] . $serverPublic, $device['auth']);
        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);

        $plain = openssl_decrypt(substr($cipher, 0, -16), 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, substr($cipher, -16));
        $this->assertNotFalse($plain, 'the device can open the message');
        $this->assertSame("\x02", substr($plain, -1), 'last-record delimiter');

        return substr($plain, 0, -1);
    }

    public function test_a_message_is_encrypted_so_only_the_device_can_read_it(): void
    {
        $device = $this->device();
        $body = WebPush::encrypt('{"title":"همّة"}', $device['public'], $device['auth']);

        $this->assertSame(4096, unpack('N', substr($body, 16, 4))[1]);
        $this->assertSame('{"title":"همّة"}', $this->decrypt($body, $device));
    }

    public function test_encryption_matches_the_example_in_rfc_8291(): void
    {
        $b = fn ($s) => WebPush::b64decode($s);
        $asPrivate = $b('yfWPiYE-n46HLnH0KqZOF1fJJU3MYrct3AELtAQ-oRw');
        $asPublic = $b('BP4z9KsN6nGRTbVYI_c7VJSPQTBtkgcy27mlmlMoZIIgDll6e3vCYLocInmYWAmS6TlzAC8wEqKK6PBru3jl7A8');
        // SEC1 private key: version 1, the 32-byte secret, curve P-256, the public point
        $der = hex2bin('30770201010420') . $asPrivate . hex2bin('a00a06082a8648ce3d030107a144034200') . $asPublic;
        $key = openssl_pkey_get_private("-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n");

        $body = WebPush::encrypt('When I grow up, I want to be a watermelon',
            $b('BCVxsr7N_eNgVRqvHtD0zTZsEc6-VV-JvLexhqUzORcxaOzi6-AYWXvTBHm4bjyPjs7Vd8pZGH6SRpkNtoIAiw4'),
            $b('BTBZMqHH6r4Tts7J_aSIgg'), $key, $b('DGv6ra1nlYgDCS1FRnbzlw'));

        $this->assertSame('DGv6ra1nlYgDCS1FRnbzlwAAEABBBP4z9KsN6nGRTbVYI_c7VJSPQTBtkgcy27mlmlMoZIIgDll6e3vCYLocInmYWAmS6TlzAC8wEqKK6PBru3jl7A_yl95bQpu6cVPTpK4Mqgkf1CXztLVBSt2Ks3oZwbuwXPXLWyouBWLVWGNWQexSgSxsj_Qulcy4a-fN',
            WebPush::b64($body));
    }

    public function test_the_push_service_can_check_the_site_signature(): void
    {
        $header = WebPush::authorization(self::ENDPOINT);
        $this->assertMatchesRegularExpression('/^vapid t=([\w-]+)\.([\w-]+)\.([\w-]+), k=([\w-]+)$/', $header);
        preg_match('/^vapid t=([\w-]+)\.([\w-]+)\.([\w-]+), k=([\w-]+)$/', $header, $m);

        $claims = json_decode(WebPush::b64decode($m[2]), true);
        $this->assertSame('https://fcm.googleapis.com', $claims['aud']);
        $this->assertGreaterThan(time(), $claims['exp']);
        $this->assertSame($m[4], WebPush::publicKey());

        // JWT signature (r || s) back to DER, checked with the public key
        $raw = WebPush::b64decode($m[3]);
        $int = fn ($b) => "\x02" . chr(strlen($b = (ord(ltrim($b, "\0")[0]) > 0x7f ? "\0" : '') . ltrim($b, "\0"))) . $b;
        $der = $int(substr($raw, 0, 32)) . $int(substr($raw, 32));
        $der = "\x30" . chr(strlen($der)) . $der;
        $this->assertSame(1, openssl_verify($m[1] . '.' . $m[2], $der, WebPush::publicKeyFromRaw(WebPush::b64decode($m[4])), OPENSSL_ALGO_SHA256));
    }

    public function test_a_device_is_saved_only_for_a_real_push_service(): void
    {
        $user = User::factory()->create();
        $device = $this->device();
        $keys = ['p256dh' => WebPush::b64($device['public']), 'auth' => WebPush::b64($device['auth'])];

        foreach (['http://fcm.googleapis.com/x', 'https://169.254.169.254/latest', 'https://evil.com/fcm.googleapis.com', 'https://googleapis.com.evil.com/x'] as $bad) {
            $this->actingAs($user)->postJson(route('push.store'), ['endpoint' => $bad, 'keys' => $keys])->assertStatus(422);
        }
        $this->actingAs($user)->postJson(route('push.store'), ['endpoint' => self::ENDPOINT, 'keys' => ['p256dh' => 'short', 'auth' => $keys['auth']]])->assertStatus(422);
        $this->assertSame(0, PushSubscription::count());

        $this->actingAs($user)->postJson(route('push.store'), ['endpoint' => self::ENDPOINT, 'keys' => $keys])->assertNoContent();

        // someone else logs in on the same phone: the device moves to them
        $next = User::factory()->create();
        $this->actingAs($next)->postJson(route('push.store'), ['endpoint' => self::ENDPOINT, 'keys' => $keys])->assertNoContent();
        $this->assertSame($next->id, PushSubscription::sole()->user_id);

        $this->actingAs($next)->deleteJson(route('push.destroy'), ['endpoint' => self::ENDPOINT])->assertNoContent();
        $this->assertSame(0, PushSubscription::count());
    }

    public function test_chat_messages_and_notifications_reach_the_phone(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 201)]);
        $device = $this->device();

        $coach = User::factory()->trainer()->create(['locale' => 'ar']);
        $trainee = User::factory()->create(['name' => 'Rami']);
        Subscription::create([
            'trainee_id' => $trainee->id, 'coach_id' => $coach->id, 'package_title' => 'Plan', 'price' => 25, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(29),
        ]);
        $chat = Conversation::between($coach->id, $trainee->id);
        PushSubscription::create([
            'user_id' => $coach->id, 'endpoint' => self::ENDPOINT, 'endpoint_hash' => hash('sha256', self::ENDPOINT),
            'public_key' => WebPush::b64($device['public']), 'auth_token' => WebPush::b64($device['auth']),
        ]);

        $this->actingAs($trainee)->postJson(route('chat.store', $chat), ['body' => 'Done with leg day!'])->assertOk();

        Http::assertSent(function (HttpRequest $request) use ($device, $chat) {
            $payload = json_decode($this->decrypt($request->body(), $device), true);

            return $request->url() === self::ENDPOINT
                && $request->hasHeader('Content-Encoding', 'aes128gcm')
                && str_starts_with($request->header('Authorization')[0], 'vapid t=')
                && $payload === ['title' => 'Rami', 'body' => 'Done with leg day!', 'url' => '/messages/' . $chat->id, 'tag' => 'chat-' . $chat->id];
        });

        // any other notification goes too, in the reader's language
        Http::fake([self::ENDPOINT => Http::response('', 201)]);
        Notify::user($coach, 'New message from :name.', ['name' => 'Rami'], '/messages/' . $chat->id);
        app()->terminate();
        Http::assertSent(fn (HttpRequest $r) => json_decode($this->decrypt($r->body(), $device), true)['body'] === 'رسالة جديدة من Rami.');
    }

    public function test_a_device_that_is_gone_is_forgotten(): void
    {
        Http::fake([self::ENDPOINT => Http::response('', 410)]);
        $device = $this->device();
        $user = User::factory()->create();
        PushSubscription::create([
            'user_id' => $user->id, 'endpoint' => self::ENDPOINT, 'endpoint_hash' => hash('sha256', self::ENDPOINT),
            'public_key' => WebPush::b64($device['public']), 'auth_token' => WebPush::b64($device['auth']),
        ]);

        WebPush::send($user, 'همّة', 'Hello', '/');
        app()->terminate();

        $this->assertSame(0, PushSubscription::count());
    }
}
