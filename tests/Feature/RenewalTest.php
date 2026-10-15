<?php

namespace Tests\Feature;

use App\Models\CoachPackage;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RenewalTest extends TestCase
{
    use RefreshDatabase;

    private User $trainee;
    private User $coach;
    private User $admin;
    private CoachPackage $package;
    private Subscription $sub;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(9, 0));

        $this->admin = User::factory()->admin()->create();
        $this->trainee = User::factory()->create(['weight' => 80, 'phone' => '0944111222']);
        $this->coach = User::factory()->trainer()->create();
        $this->coach->ensureCoachProfile()->update(['status' => 'approved']);
        $this->package = $this->coach->packages()->create([
            'title' => 'Workout plan', 'includes_workout' => true, 'includes_nutrition' => false,
            'price' => 25, 'duration_days' => 30, 'is_active' => true,
        ]);
        $this->sub = $this->subscription(['starts_at' => now()->subDays(27), 'ends_at' => now()->addDays(3)]);
        WorkoutPlan::create([
            'user_id' => $this->trainee->id, 'trainer_id' => $this->coach->id, 'subscription_id' => $this->sub->id,
            'title' => 'Push pull legs', 'plan_details' => json_encode(['Saturday' => [['muscle' => 'Chest', 'exercise' => 'Bench', 'sets' => '4', 'reps' => '8', 'exercise_id' => null]]]),
        ]);
    }

    private function subscription(array $attributes): Subscription
    {
        return Subscription::create($attributes + [
            'trainee_id' => $this->trainee->id, 'coach_id' => $this->coach->id, 'package_id' => $this->package->id,
            'package_title' => 'Workout plan', 'includes_workout' => true, 'includes_nutrition' => false,
            'duration_days' => 30, 'price' => 25, 'status' => 'active',
            'intake' => ['goal' => 'muscle_gain', 'level' => 'beginner', 'days_per_week' => 4, 'weight' => 78],
        ]);
    }

    private function renewAndConfirm(): Subscription
    {
        $this->actingAs($this->trainee)->post(route('subscriptions.renew', $this->sub));
        $renewal = Subscription::latest('id')->first();
        $renewal->update(['status' => 'payment_review', 'payment_submitted_at' => now()]);
        $this->actingAs($this->admin)->post(route('admin.subscriptions.confirm', $renewal));

        return $renewal->fresh();
    }

    public function test_renew_in_one_tap_goes_straight_to_payment(): void
    {
        $response = $this->actingAs($this->trainee)->post(route('subscriptions.renew', $this->sub));

        $renewal = Subscription::latest('id')->first();
        $response->assertRedirect(route('subscriptions.pay', $renewal));
        $this->assertSame('awaiting_payment', $renewal->status);
        $this->assertSame($this->package->id, $renewal->package_id);
        $this->assertSame('muscle_gain', $renewal->intake['goal']);
        $this->assertEquals(80, $renewal->intake['weight'], 'answers carry the current weight');

        // a second tap opens the same payment instead of a new subscription
        $this->actingAs($this->trainee)->post(route('subscriptions.renew', $this->sub))->assertRedirect(route('subscriptions.pay', $renewal));
        $this->assertSame(2, Subscription::count());
    }

    public function test_confirmed_renewal_starts_when_the_current_one_ends(): void
    {
        $renewal = $this->renewAndConfirm();

        $this->assertTrue($renewal->starts_at->equalTo($this->sub->ends_at));
        $this->assertTrue($renewal->isUpcoming());
        $this->assertFalse($renewal->isRunning());
        $this->assertTrue($this->sub->fresh()->isRunning());
        $this->assertSame(1, Subscription::running()->count(), 'the renewal is not counted twice');

        $this->actingAs($this->trainee)->get(route('trainee.dashboard'))
            ->assertSee(__('Renewed: continues until :date', ['date' => $renewal->ends_at->translatedFormat('j M Y')]));
        $this->actingAs($this->coach)->get(route('trainer.dashboard'))->assertSee(__('Renewed'));
        $this->assertStringContainsString('renewed', $this->coach->notifications()->latest()->first()->data['message']);
    }

    public function test_ending_soon_shows_the_renew_button(): void
    {
        $this->actingAs($this->trainee)->get(route('trainee.dashboard'))
            ->assertSee(route('subscriptions.renew', $this->sub))
            ->assertSee(trans_choice('{1} Ends tomorrow|[2,*] Ends in :count days', 3));
    }

    public function test_package_no_longer_offered_sends_to_the_coach_page(): void
    {
        $this->package->update(['is_active' => false]);

        $this->actingAs($this->trainee)->post(route('subscriptions.renew', $this->sub))
            ->assertRedirect(route('coaches.show', $this->coach))->assertSessionHas('error');
        $this->assertSame(1, Subscription::count());
    }

    public function test_only_the_owner_can_renew(): void
    {
        $this->actingAs(User::factory()->create())->post(route('subscriptions.renew', $this->sub))->assertNotFound();
    }

    public function test_plan_carries_over_until_the_coach_sends_one(): void
    {
        $renewal = $this->renewAndConfirm();
        $this->travel(4)->days();   // the renewal has started

        $this->actingAs($this->trainee)->get(route('trainee.dashboard'))
            ->assertSee(route('trainee.schedule', ['subscription' => $this->sub->id]), false);

        // the builder starts from the last plan
        $this->actingAs($this->coach)->get(route('coach.plans.edit', [$renewal, 'workout']))->assertSee('Push pull legs');

        // or the coach sends it again as it is
        $this->actingAs($this->coach)->post(route('coach.plans.repeat', [$renewal, 'workout']))
            ->assertRedirect(route('coach.clients.show', $renewal));
        $this->assertSame('Push pull legs', $renewal->fresh()->workoutPlan->title);
        $this->actingAs($this->coach)->post(route('coach.plans.repeat', [$renewal, 'workout']))->assertNotFound();
    }

    public function test_reminders_before_and_after_the_end(): void
    {
        $this->artisan('app:subscription-reminders');
        $this->artisan('app:subscription-reminders');
        $this->assertSame(1, $this->trainee->notifications()->count());
        $this->assertSame(1, $this->coach->notifications()->count());

        $this->travel(4)->days();
        $this->artisan('app:subscription-reminders');
        $this->assertSame(2, $this->trainee->notifications()->count(), 'one more after it ended');
    }

    public function test_no_renewal_reminder_once_renewing(): void
    {
        $this->actingAs($this->trainee)->post(route('subscriptions.renew', $this->sub));
        $this->artisan('app:subscription-reminders');

        $this->assertSame(0, $this->trainee->notifications()->where('data', 'like', '%renew-%')->count());
    }

    public function test_unpaid_and_unreviewed_payments_are_followed_up(): void
    {
        $unpaid = $this->subscription(['status' => 'awaiting_payment', 'starts_at' => null, 'ends_at' => null]);
        $review = $this->subscription(['status' => 'payment_review', 'payment_submitted_at' => now(), 'starts_at' => null, 'ends_at' => null]);

        $this->artisan('app:subscription-reminders');
        $this->assertSame(0, $this->trainee->notifications()->where('data', 'like', '%"pay-%')->count());
        $this->assertSame(0, $this->admin->notifications()->count());

        $this->travel(2)->days();
        $this->artisan('app:subscription-reminders');
        $this->artisan('app:subscription-reminders');
        $this->assertSame(1, $this->trainee->notifications()->where('data', 'like', '%"pay-' . $unpaid->id . '"%')->count());
        $this->assertSame(1, $this->admin->notifications()->where('data', 'like', '%"review-late-' . $review->id . '"%')->count());
    }

    public function test_late_plan_reaches_coach_and_admin(): void
    {
        $fresh = $this->subscription(['starts_at' => now(), 'ends_at' => now()->addDays(30)]);

        $this->travel(2)->days();
        $this->artisan('app:subscription-reminders');
        $this->assertSame(1, $this->coach->notifications()->where('data', 'like', '%"plan-due-' . $fresh->id . '"%')->count());
        $this->assertSame(0, $this->admin->notifications()->count());

        $this->travel(2)->days();
        $this->artisan('app:subscription-reminders');
        $this->artisan('app:subscription-reminders');
        $this->assertSame(1, $this->coach->notifications()->where('data', 'like', '%"plan-late-' . $fresh->id . '"%')->count());
        $this->assertSame(1, $this->admin->notifications()->where('data', 'like', '%"plan-late-admin-' . $fresh->id . '"%')->count());
    }
}
