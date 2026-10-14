<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminResetLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_link_lets_the_user_set_a_new_password(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create(['email' => 'c@example.com', 'phone' => '0944123456']);

        $this->actingAs($admin)->post(route('admin.users.resetLink', $user->id))->assertSessionHas('reset_link');
        $link = session('reset_link');
        $this->assertStringStartsWith('https://wa.me/963944123456?text=', $link['whatsapp']);

        // the user opens the link (logged out) and picks a new password
        $this->app['auth']->forgetGuards();
        $this->flushSession();
        $this->get($link['url'])->assertOk();
        preg_match('#reset-password/([^?]+)#', $link['url'], $m);
        $this->post(route('password.update'), [
            'token' => $m[1], 'email' => 'c@example.com',
            'password' => 'new-secret-1', 'password_confirmation' => 'new-secret-1',
        ])->assertRedirect(route('home'));

        $this->assertTrue(Hash::check('new-secret-1', $user->fresh()->password));
    }

    public function test_no_link_for_admins_or_non_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();
        $trainee = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.users.resetLink', $otherAdmin->id))->assertForbidden();
        // non-admins are sent back to their own home page
        $this->actingAs($trainee)->post(route('admin.users.resetLink', $admin->id))->assertRedirect(route('trainee.dashboard'));
        $this->assertNull(session('reset_link'));
    }
}
