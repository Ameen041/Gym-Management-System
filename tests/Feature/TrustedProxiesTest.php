<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** The visitor's address (used by the login limits) can't be faked with X-Forwarded-For. */
class TrustedProxiesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Route::middleware('web')->get('/_test/ip', fn () => request()->ip() . '|' . (request()->isSecure() ? 'https' : 'http'));
    }

    private function seenAs(string $remote): string
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $remote])
            ->withHeaders(['X-Forwarded-For' => '6.6.6.6', 'X-Forwarded-Proto' => 'https'])
            ->get('http://localhost/_test/ip')->getContent();
    }

    public function test_by_default_no_proxy_is_trusted(): void
    {
        config(['trustedproxy.proxies' => null]);

        $this->assertSame('203.0.113.9|http', $this->seenAs('203.0.113.9'));
    }

    public function test_behind_a_trusted_proxy_its_headers_are_used(): void
    {
        config(['trustedproxy.proxies' => '*']);
        $this->assertSame('6.6.6.6|https', $this->seenAs('172.18.0.4'));

        config(['trustedproxy.proxies' => '172.18.0.4']);
        $this->assertSame('6.6.6.6|https', $this->seenAs('172.18.0.4'));
        $this->assertSame('203.0.113.9|http', $this->seenAs('203.0.113.9'));
    }
}
