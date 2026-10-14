<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_guessing_is_slowed_down(): void
    {
        $user = User::factory()->create(['email' => 'a@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'a@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        }

        // even the right password waits now
        $this->post('/login', ['email' => 'a@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_right_password_logs_in_and_resets_the_counter(): void
    {
        User::factory()->create(['email' => 'b@example.com']);

        $this->post('/login', ['email' => 'b@example.com', 'password' => 'wrong']);
        $this->post('/login', ['email' => 'b@example.com', 'password' => 'password'])->assertRedirect(route('trainee.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_pages_send_security_headers(): void
    {
        $this->get('/')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
