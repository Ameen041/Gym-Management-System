<?php

namespace Tests\Feature;

use App\Http\Controllers\InviteController;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InviteLinkTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->coach = User::factory()->trainer()->create(['name' => 'Coach Sami']);
        $this->coach->ensureCoachProfile()->update(['status' => 'approved']);
    }

    private function link(): string
    {
        return $this->coach->coachProfile->fresh()->inviteUrl();
    }

    private function signUp(array $cookies = [])
    {
        return $this->withCookies($cookies)->post(route('register'), [
            'name' => 'Rami', 'email' => 'rami@example.com', 'password' => 'secret123', 'password_confirmation' => 'secret123',
            'role' => 'trainee', 'terms' => '1',
        ]);
    }

    private function confirmPayment(User $trainee, User $coach): Subscription
    {
        $sub = Subscription::create([
            'trainee_id' => $trainee->id, 'coach_id' => $coach->id, 'package_title' => 'Plan', 'price' => 40,
            'duration_days' => 30, 'status' => 'payment_review',
        ]);
        $this->actingAs($this->admin)->post(route('admin.subscriptions.confirm', $sub));

        return $sub->fresh();
    }

    public function test_the_link_remembers_the_coach_and_counts_people(): void
    {
        $code = basename($this->link());

        $this->get($this->link())->assertRedirect(route('coaches.show', $this->coach))->assertCookie(InviteController::COOKIE, $code);
        $this->withCookie(InviteController::COOKIE, $code)->get($this->link());   // the same person again

        $this->assertSame(1, $this->coach->coachProfile->fresh()->invite_visits);
    }

    public function test_a_client_who_signs_up_from_the_link_pays_the_lower_commission(): void
    {
        $code = basename($this->link());
        $this->signUp([InviteController::COOKIE => $code])->assertRedirect(route('coaches.show', $this->coach));
        $trainee = User::where('email', 'rami@example.com')->sole();
        $this->assertSame($this->coach->id, (int) $trainee->invited_by);

        $sub = $this->confirmPayment($trainee, $this->coach);
        $this->assertSame(Subscription::SOURCE_COACH_LINK, $sub->source);
        $this->assertEquals(Setting::inviteCommissionRate(), $sub->commission_rate);
        $this->assertSame(2.0, (float) $sub->commission_amount);   // 5% of 40
        $this->assertSame(38.0, $sub->coach_amount);

        // a renewal stays the coach's client; another coach gets the normal rate
        $this->assertSame(Subscription::SOURCE_COACH_LINK, $this->confirmPayment($trainee, $this->coach)->source);
        $other = User::factory()->trainer()->create();
        $this->assertSame(6.0, (float) $this->confirmPayment($trainee, $other)->commission_amount);   // 15% of 40

        $this->actingAs($this->coach)->get(route('trainer.dashboard'))
            ->assertSee(__('Your invite link'))->assertSee($this->link());
    }

    public function test_people_who_already_had_an_account_do_not_count(): void
    {
        $trainee = User::factory()->create();
        $this->withCookie(InviteController::COOKIE, basename($this->link()))->actingAs($trainee)->get(route('trainee.dashboard'));

        $this->assertSame(Subscription::SOURCE_HIMMA, $this->confirmPayment($trainee, $this->coach)->source);
    }

    public function test_a_coach_who_is_not_approved_has_no_working_link(): void
    {
        $code = basename($this->link());
        $this->coach->coachProfile->update(['status' => 'pending']);

        $this->get(route('invite', $code))->assertRedirect(route('coaches.index'));
        $this->signUp([InviteController::COOKIE => $code])->assertRedirect(route('trainee.dashboard'));
        $this->assertNull(User::where('email', 'rami@example.com')->value('invited_by'));
        $this->actingAs($this->coach)->get(route('trainer.dashboard'))->assertSee(__('Your link is ready once your profile is approved.'));
    }

    public function test_the_admin_sets_the_rate(): void
    {
        $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'commission_rate' => 15, 'invite_commission_rate' => 7.5, 'payout_hold_days' => 7,
        ])->assertSessionHasNoErrors();

        $this->assertSame(7.5, Setting::inviteCommissionRate());
    }
}
