<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DemoUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        putenv('ADMIN_EMAIL');
        putenv('ADMIN_PASSWORD');
        parent::tearDown();
    }

    private function seedAsProduction(?string $email, ?string $password): void
    {
        $email === null ? putenv('ADMIN_EMAIL') : putenv("ADMIN_EMAIL=$email");
        $password === null ? putenv('ADMIN_PASSWORD') : putenv("ADMIN_PASSWORD=$password");
        $this->app['env'] = 'production';
        $this->app->make(DemoUsersSeeder::class)->run();
        $this->app['env'] = 'testing';
    }

    public function test_live_site_has_no_default_admin(): void
    {
        $this->seedAsProduction(null, null);

        $this->assertSame(0, User::count());
    }

    public function test_live_site_refuses_a_short_admin_password(): void
    {
        $this->seedAsProduction('owner@example.com', 'short');

        $this->assertSame(0, User::count());
    }

    public function test_live_site_creates_the_admin_from_settings_only(): void
    {
        $this->seedAsProduction('owner@example.com', 'a-long-password');

        $this->assertSame(['owner@example.com'], User::pluck('email')->all());
        $this->assertSame('admin', User::first()->role);
    }
}
