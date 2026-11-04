<?php

namespace Tests\Feature;

use App\Models\CoachPackage;
use App\Models\PlanDraft;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** What the coach types in a plan builder is kept as a draft, so leaving the page loses nothing. */
class PlanDraftTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;
    private Subscription $sub;

    protected function setUp(): void
    {
        parent::setUp();
        $trainee = User::factory()->create();
        $this->coach = User::factory()->trainer()->create();
        $this->coach->ensureCoachProfile()->update(['status' => 'approved']);
        $package = CoachPackage::create([
            'coach_id' => $this->coach->id, 'title' => 'Full coaching', 'includes_workout' => true,
            'includes_nutrition' => true, 'price' => 40, 'duration_days' => 30, 'is_active' => true,
        ]);
        $this->sub = Subscription::create([
            'trainee_id' => $trainee->id, 'coach_id' => $this->coach->id, 'package_id' => $package->id,
            'package_title' => 'Full coaching', 'includes_workout' => true, 'includes_nutrition' => true,
            'duration_days' => 30, 'price' => 40, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays(30),
            'intake' => ['goal' => 'muscle_gain', 'level' => 'beginner', 'days_per_week' => 3, 'weight' => 78],
        ]);
    }

    private function workoutForm(array $rows, string $title = 'Week 1'): array
    {
        return [
            'title' => $title,
            'day' => array_column($rows, 0),
            'exercise_id' => array_fill(0, count($rows), ''),
            'exercise' => array_column($rows, 1),
            'muscle' => array_fill(0, count($rows), 'Chest'),
            'sets' => array_fill(0, count($rows), '3'),
            'reps' => array_fill(0, count($rows), '10'),
        ];
    }

    private function saveDraft(string $type, array $form, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->coach)->post(route('coach.plans.draft', [$this->sub, $type]), $form);
    }

    public function test_the_workout_the_coach_left_comes_back_and_the_trainee_sees_nothing_yet(): void
    {
        $this->saveDraft('workout', $this->workoutForm([['Saturday', 'Bench press'], ['Saturday', 'Squat']], 'Strength'))
            ->assertOk()->assertJson(['saved' => true]);

        $this->assertNull($this->sub->fresh()->workoutPlan);
        $this->actingAs($this->coach)->get(route('coach.plans.edit', [$this->sub, 'workout']))->assertOk()
            ->assertSee(__('Start over'))
            ->assertSee('value="Strength"', false)
            ->assertSeeInOrder(['Bench press', 'Squat']);
    }

    public function test_sending_the_plan_removes_the_draft(): void
    {
        $form = $this->workoutForm([['Monday', 'Deadlift']]);
        $this->saveDraft('workout', $form);
        $this->actingAs($this->coach)->post(route('coach.plans.save', [$this->sub, 'workout']), $form)->assertSessionHasNoErrors();

        $this->assertSame(0, PlanDraft::count());
        $this->actingAs($this->coach)->get(route('coach.plans.edit', [$this->sub, 'workout']))
            ->assertDontSee(__('Back to the sent plan'))->assertSee('Deadlift');
    }

    public function test_discarding_goes_back_to_the_sent_plan(): void
    {
        $this->actingAs($this->coach)->post(route('coach.plans.save', [$this->sub, 'workout']), $this->workoutForm([['Monday', 'Deadlift']]));
        // all rows removed is kept as it is, not replaced by the sent plan
        $this->saveDraft('workout', ['title' => 'Week 1']);
        $this->actingAs($this->coach)->get(route('coach.plans.edit', [$this->sub, 'workout']))
            ->assertSee(__('Back to the sent plan'))->assertDontSee('Deadlift');

        $this->actingAs($this->coach)->delete(route('coach.plans.draft.discard', [$this->sub, 'workout']))
            ->assertRedirect(route('coach.plans.edit', [$this->sub, 'workout']));
        $this->assertSame(0, PlanDraft::count());
        $this->actingAs($this->coach)->get(route('coach.plans.edit', [$this->sub, 'workout']))->assertSee('Deadlift');
    }

    public function test_the_nutrition_plan_the_coach_left_comes_back(): void
    {
        $this->saveDraft('nutrition', [
            'title' => 'Cutting',
            'targets' => ['calories' => 2100, 'protein' => 160, 'carbs' => 200, 'fat' => 70],
            'meals' => ['m1' => ['day' => 'Sunday', 'number' => 1, 'note' => 'Before training', 'items' => [
                'i1' => ['food_id' => '', 'name' => 'Oats with milk', 'qty' => '', 'unit' => 'g', 'calories' => 350, 'protein' => 20, 'carbs' => 45, 'fat' => 10],
            ]]],
        ])->assertOk();

        $this->actingAs($this->coach)->get(route('coach.plans.edit', [$this->sub, 'nutrition']))->assertOk()
            ->assertSee('value="Cutting"', false)->assertSee('Oats with milk')->assertSee('Before training')->assertSee('value="2100"', false);
    }

    public function test_only_the_coach_of_the_client_keeps_a_draft(): void
    {
        $other = User::factory()->trainer()->create();
        $this->saveDraft('workout', $this->workoutForm([['Monday', 'Deadlift']]), $other)->assertNotFound();
        $this->assertSame(0, PlanDraft::count());
    }
}
