<?php

namespace Tests\Feature;

use App\Models\CoachPackage;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthAndTermsTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;
    private User $trainee;
    private CoachPackage $package;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coach = User::factory()->trainer()->create();
        $this->coach->ensureCoachProfile()->update(['status' => 'approved']);
        $this->package = CoachPackage::create([
            'coach_id' => $this->coach->id, 'title' => 'Full coaching', 'includes_workout' => true, 'includes_nutrition' => true,
            'price' => 40, 'duration_days' => 30, 'is_active' => true,
        ]);
        $this->trainee = User::factory()->create();
    }

    private function subscribe(array $health, array $extra = [])
    {
        return $this->actingAs($this->trainee)->post(route('subscriptions.store', [$this->coach, $this->package]), $extra + [
            'goal' => 'weight_loss', 'level' => 'beginner', 'days_per_week' => 4,
            'age' => 25, 'height' => 175, 'weight' => 80, 'phone' => '0933123456',
            'health' => $health, 'consent' => '1',
        ]);
    }

    private function answers(array $yes = []): array
    {
        return collect(Subscription::HEALTH_QUESTIONS)->map(fn ($q, $key) => in_array($key, $yes, true) ? '1' : '0')->all();
    }

    public function test_sign_up_needs_the_terms_and_remembers_when_they_were_accepted(): void
    {
        $form = ['name' => 'Sara', 'email' => 'sara@example.com', 'password' => 'secret123', 'password_confirmation' => 'secret123', 'role' => 'trainee'];

        $this->post(route('register'), $form)->assertSessionHasErrors('terms', null, 'register');
        $this->assertGuest();

        $this->post(route('register'), $form + ['terms' => '1'])->assertRedirect(route('trainee.dashboard'));
        $this->assertNotNull(User::where('email', 'sara@example.com')->value('terms_accepted_at'));
    }

    public function test_terms_and_privacy_pages_in_both_languages(): void
    {
        $this->get(route('terms'))->assertOk()->assertSee('التنبيه الصحي')->assertSee('id="refunds"', false)
            ->assertSee('خلال ' . Subscription::PLAN_DUE_DAYS . ' أيام');
        $this->get(route('privacy'))->assertOk()->assertSee('سياسة الخصوصية');
        $this->get(route('home'))->assertSee(route('terms'))->assertSee(route('privacy'));

        $this->withSession(['locale' => 'en'])->get(route('terms'))->assertOk()->assertSee('Health notice')->assertSee('id="health"', false);
        $this->withSession(['locale' => 'en'])->get(route('privacy'))->assertOk()->assertSee('never sells your data');
    }

    public function test_every_health_question_needs_an_answer_and_the_notice_must_be_accepted(): void
    {
        $answers = $this->answers();
        unset($answers['pregnancy']);
        $this->subscribe($answers)->assertSessionHasErrors('health');
        $this->subscribe($this->answers(), ['consent' => ''])->assertSessionHasErrors('consent');
        $this->assertSame(0, Subscription::count());

        $this->subscribe($this->answers())->assertRedirect();
        $sub = Subscription::sole();
        $this->assertSame([], $sub->healthFlags());
        $this->assertFalse($sub->intake['health']['heart']);
        $this->assertArrayHasKey('health_confirmed_at', $sub->intake);
    }

    public function test_a_yes_needs_details_and_a_doctor_and_warns_the_coach(): void
    {
        $this->subscribe($this->answers(['joints']))->assertSessionHasErrors(['health_notes', 'doctor']);

        $this->subscribe($this->answers(['joints']), ['health_notes' => 'Right knee pain on squats', 'doctor' => '1'])->assertRedirect();
        $sub = Subscription::sole();
        $this->assertSame(['joints'], $sub->healthFlags());

        // the coach is told when the payment is confirmed, and sees the alert
        $sub->update(['status' => 'payment_review']);
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.subscriptions.confirm', $sub));
        $this->assertTrue($this->coach->notifications->contains(fn ($n) => str_contains($n->data['message'], 'health question')));

        $this->actingAs($this->coach)->get(route('coach.clients.show', $sub))
            ->assertOk()->assertSee(__('Bone, joint or muscle problem'))->assertSee('Right knee pain on squats');
        $this->actingAs($this->coach)->get(route('trainer.dashboard'))->assertSee(__('Health alert'));
    }

    public function test_health_questions_have_arabic_lines(): void
    {
        $arabic = json_decode(file_get_contents(base_path('lang/ar.json')), true);
        foreach ([...array_values(Subscription::HEALTH_QUESTIONS), ...array_values(Subscription::HEALTH_LABELS)] as $line) {
            $this->assertArrayHasKey($line, $arabic);
        }
        $this->assertSame(array_keys(Subscription::HEALTH_QUESTIONS), array_keys(Subscription::HEALTH_LABELS));
    }

    public function test_plans_show_the_health_notice(): void
    {
        $sub = Subscription::create([
            'trainee_id' => $this->trainee->id, 'coach_id' => $this->coach->id, 'package_title' => 'Plan', 'price' => 40,
            'includes_workout' => true, 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(29),
        ]);
        \App\Models\WorkoutPlan::create([
            'user_id' => $this->trainee->id, 'trainer_id' => $this->coach->id, 'subscription_id' => $sub->id, 'title' => 'Plan',
            'plan_details' => json_encode(['Saturday' => [['muscle' => 'Chest', 'exercise' => 'Bench press', 'sets' => '3', 'reps' => '10']]]),
        ]);

        $this->actingAs($this->trainee)->get(route('trainee.schedule', ['subscription' => $sub->id]))
            ->assertOk()->assertSee(__('This plan is guidance from your coach, not medical advice. Stop and see a doctor if you feel chest pain, dizziness or unusual pain.'));
    }
}
