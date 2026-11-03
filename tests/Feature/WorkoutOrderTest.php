<?php

namespace Tests\Feature;

use App\Models\CoachPackage;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The coach puts the exercises of a day in order in the builder; the plan keeps that order. */
class WorkoutOrderTest extends TestCase
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
            'coach_id' => $this->coach->id, 'title' => 'Workout plan', 'includes_workout' => true,
            'includes_nutrition' => false, 'price' => 25, 'duration_days' => 30, 'is_active' => true,
        ]);
        $this->sub = Subscription::create([
            'trainee_id' => $trainee->id, 'coach_id' => $this->coach->id, 'package_id' => $package->id,
            'package_title' => 'Workout plan', 'includes_workout' => true, 'includes_nutrition' => false,
            'duration_days' => 30, 'price' => 25, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays(30),
            'intake' => ['goal' => 'muscle_gain', 'level' => 'beginner', 'days_per_week' => 3, 'weight' => 78],
        ]);
    }

    private function save(array $rows): void
    {
        $this->actingAs($this->coach)->post(route('coach.plans.save', [$this->sub, 'workout']), [
            'title' => 'Week 1',
            'day' => array_column($rows, 0),
            'exercise_id' => array_fill(0, count($rows), ''),
            'exercise' => array_column($rows, 1),
            'muscle' => array_fill(0, count($rows), 'Chest'),
            'sets' => array_fill(0, count($rows), '3'),
            'reps' => array_fill(0, count($rows), '10'),
        ])->assertSessionHasNoErrors();
    }

    private function order(): array
    {
        $details = json_decode($this->sub->fresh()->workoutPlan->plan_details, true);

        return array_map(fn ($day) => array_column($day, 'exercise'), $details);
    }

    public function test_the_builder_has_a_handle_to_drag_each_exercise(): void
    {
        $this->save([['Saturday', 'Bench press'], ['Saturday', 'Squat']]);

        $this->actingAs($this->coach)->get(route('coach.plans.edit', [$this->sub, 'workout']))
            ->assertOk()
            ->assertSee('data-grip', false)
            ->assertSeeInOrder(['Bench press', 'Squat']);
    }

    public function test_a_new_order_is_saved_as_shown(): void
    {
        $this->save([['Saturday', 'Bench press'], ['Saturday', 'Squat'], ['Saturday', 'Plank'], ['Monday', 'Row']]);
        // the coach drags Plank to the top: the rows are sent in their new order
        $this->save([['Saturday', 'Plank'], ['Saturday', 'Bench press'], ['Saturday', 'Squat'], ['Monday', 'Row']]);

        $this->assertSame(['Saturday' => ['Plank', 'Bench press', 'Squat'], 'Monday' => ['Row']], $this->order());
        $this->actingAs($this->coach)->get(route('coach.plans.edit', [$this->sub, 'workout']))
            ->assertSeeInOrder(['Plank', 'Bench press', 'Squat']);
    }
}
