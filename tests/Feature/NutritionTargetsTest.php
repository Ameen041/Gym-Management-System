<?php

namespace Tests\Feature;

use App\Models\NutritionPlan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NutritionTargetsTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;
    private User $trainee;
    private Subscription $sub;

    protected function setUp(): void
    {
        parent::setUp();
        $this->coach = User::factory()->trainer()->create();
        $this->trainee = User::factory()->create(['weight' => 80]);
        $this->sub = Subscription::create([
            'trainee_id' => $this->trainee->id, 'coach_id' => $this->coach->id, 'package_title' => 'Full coaching',
            'includes_workout' => true, 'includes_nutrition' => true, 'duration_days' => 30, 'price' => 40,
            'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(29),
            'intake' => ['goal' => 'weight_loss', 'weight' => 82],
        ]);
    }

    private function send(array $targets)
    {
        return $this->actingAs($this->coach)->post(route('coach.plans.save', [$this->sub, 'nutrition']), [
            'title'   => 'Cutting',
            'targets' => $targets,
            'meals'   => ['m1' => ['day' => 'Saturday', 'number' => 1, 'items' => [
                'i1' => ['name' => 'Chicken and rice', 'calories' => 650, 'protein' => 50, 'carbs' => 70, 'fat' => 15],
            ]]],
        ]);
    }

    public function test_the_builder_starts_with_the_daily_targets(): void
    {
        $this->actingAs($this->coach)->get(route('coach.plans.edit', [$this->sub, 'nutrition']))
            ->assertOk()->assertSee(__('Daily targets'))->assertSee('name="targets[calories]"', false)
            ->assertSee('data-weight="80"', false);
    }

    public function test_targets_are_saved_with_the_plan_and_shown_to_the_client(): void
    {
        $this->send(['calories' => 2200, 'protein' => 160, 'carbs' => 230, 'fat' => 70])
            ->assertRedirect(route('coach.clients.show', $this->sub));

        $this->assertSame(['calories' => 2200, 'protein' => 160, 'carbs' => 230, 'fat' => 70], NutritionPlan::sole()->targets);

        $this->actingAs($this->trainee)->get(route('trainee.diet', ['subscription' => $this->sub->id]))
            ->assertOk()->assertSee(__('Your daily target'))->assertSee('2200');

        // the builder opens again with the same targets
        $this->actingAs($this->coach)->get(route('coach.plans.edit', [$this->sub, 'nutrition']))
            ->assertSee('name="targets[protein]" value="160"', false);
    }

    public function test_a_plan_needs_sensible_targets(): void
    {
        $this->send([])->assertSessionHasErrors(['targets.calories', 'targets.protein', 'targets.carbs', 'targets.fat']);
        $this->send(['calories' => 300, 'protein' => 160, 'carbs' => 230, 'fat' => 70])->assertSessionHasErrors('targets.calories');
        $this->send(['calories' => 2200, 'protein' => 900, 'carbs' => 230, 'fat' => 70])->assertSessionHasErrors('targets.protein');
        $this->assertSame(0, NutritionPlan::count());
    }

    public function test_a_renewal_sends_the_last_plan_with_its_targets(): void
    {
        $this->send(['calories' => 2200, 'protein' => 160, 'carbs' => 230, 'fat' => 70]);
        $renewal = $this->sub->replicate();
        $renewal->fill(['starts_at' => $this->sub->ends_at, 'ends_at' => $this->sub->ends_at->copy()->addDays(30)])->save();
        $this->travelTo($this->sub->ends_at->copy()->addHour());

        $this->actingAs($this->coach)->post(route('coach.plans.repeat', [$renewal, 'nutrition']))->assertRedirect();

        $this->assertSame(2200, NutritionPlan::where('subscription_id', $renewal->id)->sole()->targets['calories']);
    }
}
