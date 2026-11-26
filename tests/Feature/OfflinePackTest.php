<?php

namespace Tests\Feature;

use App\Models\CoachPackage;
use App\Models\Exercise;
use App\Models\NutritionPlan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WorkoutPlan;
use Database\Seeders\ExercisesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Without internet (a gym basement), the app keeps working: the phone keeps the person's pages, and a trainee's
 * plans with every exercise in them and its pictures (/offline-pack, kept by public/sw.js).
 */
class OfflinePackTest extends TestCase
{
    use RefreshDatabase;

    private User $trainee;
    private User $coach;
    private Subscription $sub;
    private WorkoutPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ExercisesSeeder::class);
        $this->trainee = User::factory()->create();
        $this->coach = User::factory()->trainer()->create();
        $package = CoachPackage::create([
            'coach_id' => $this->coach->id, 'title' => 'Full', 'includes_workout' => true, 'includes_nutrition' => true,
            'price' => 25, 'duration_days' => 30, 'is_active' => true,
        ]);
        $this->sub = Subscription::create([
            'trainee_id' => $this->trainee->id, 'coach_id' => $this->coach->id, 'package_id' => $package->id,
            'package_title' => 'Full', 'includes_workout' => true, 'includes_nutrition' => true, 'duration_days' => 30,
            'price' => 25, 'status' => 'active', 'starts_at' => now()->subDays(3), 'ends_at' => now()->addDays(27),
        ]);
        $row = fn (string $name) => ['muscle' => 'Back', 'exercise' => $name, 'sets' => '3', 'reps' => '10',
            'exercise_id' => Exercise::where('name', $name)->value('id')];
        $this->plan = WorkoutPlan::create([
            'user_id' => $this->trainee->id, 'trainer_id' => $this->coach->id, 'subscription_id' => $this->sub->id, 'title' => 'Week 1',
            'plan_details' => json_encode([
                'Saturday' => [$row('Chin-Up'), $row('One-Arm Dumbbell Row')],
                'Monday'   => [$row('Chin-Up'), ['muscle' => 'Legs', 'exercise' => 'Walking', 'sets' => '1', 'reps' => '20m']],
            ]),
        ]);
        NutritionPlan::create([
            'user_id' => $this->trainee->id, 'trainer_id' => $this->coach->id, 'subscription_id' => $this->sub->id,
            'title' => 'Cut', 'plan_details' => json_encode([]),
        ]);
    }

    public function test_a_trainee_keeps_their_plans_the_exercises_in_them_and_the_pictures(): void
    {
        $pack = $this->actingAs($this->trainee)->getJson(route('offline.pack'))->assertOk()->json();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $pack['version']);
        foreach (['/trainee-dashboard', '/workout-plan', '/nutrition-plan', '/exercises', '/messages', '/notifications',
                  '/workout-plan?subscription=' . $this->sub->id, '/nutrition-plan?subscription=' . $this->sub->id] as $page) {
            $this->assertContains($page, $pack['pages']);
        }

        // every exercise in the plan, once, with both pictures of its page
        foreach (['Chin-Up', 'One-Arm Dumbbell Row'] as $name) {
            $exercise = Exercise::where('name', $name)->sole();
            $this->assertSame(1, count(array_keys($pack['pages'], '/exercises/' . $exercise->id)), $name);
            $this->assertCount(2, $exercise->frame_urls, $name);
            foreach ($exercise->frame_urls as $url) {
                $this->assertContains(parse_url($url, PHP_URL_PATH), $pack['files'], $name);
            }
        }
        $this->assertNotContains('/exercises/' . Exercise::where('name', 'Barbell Back Squat')->value('id'), $pack['pages']);

        // the small pictures of the whole library, and the coach's photo, as paths on this site
        $squat = Exercise::where('name', 'Barbell Back Squat')->sole();
        $this->assertContains(parse_url($squat->thumbnail, PHP_URL_PATH), $pack['files']);
        $this->assertContains('/images/avatar.svg', $pack['files']);
        foreach ([...$pack['pages'], ...$pack['files']] as $path) {
            $this->assertStringStartsWith('/', $path);
        }
    }

    public function test_the_version_changes_only_when_a_plan_changes(): void
    {
        $first = $this->actingAs($this->trainee)->getJson(route('offline.pack'))->json('version');
        $this->assertSame($first, $this->getJson(route('offline.pack'))->json('version'));

        $this->travel(5)->minutes();
        $this->plan->update(['title' => 'Week 2']);

        $this->assertNotSame($first, $this->getJson(route('offline.pack'))->json('version'));
    }

    public function test_a_coach_keeps_their_running_clients(): void
    {
        $other = Subscription::create([
            'trainee_id' => User::factory()->create()->id, 'coach_id' => User::factory()->trainer()->create()->id,
            'package_title' => 'Other', 'price' => 10, 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(20),
        ]);

        $pack = $this->actingAs($this->coach)->getJson(route('offline.pack'))->assertOk()->json();

        $this->assertContains('/trainer-dashboard', $pack['pages']);
        $this->assertContains('/coach/clients/' . $this->sub->id, $pack['pages']);
        $this->assertNotContains('/coach/clients/' . $other->id, $pack['pages']);
        $this->assertNotContains('/trainee-dashboard', $pack['pages']);
    }

    public function test_only_coaches_and_trainees_get_a_pack(): void
    {
        $this->getJson(route('offline.pack'))->assertUnauthorized();
        $this->actingAs(User::factory()->admin()->create())->get(route('offline.pack'))->assertRedirect();
    }

    public function test_the_pages_of_coaches_and_trainees_ask_the_phone_to_keep_them(): void
    {
        $this->actingAs($this->trainee)->get(route('trainee.dashboard'))->assertOk()
            ->assertSee('<meta name="offline-pack" content="/offline-pack">', false)
            ->assertSee('js/offline.js', false);
        $this->actingAs($this->coach)->get(route('trainer.dashboard'))->assertOk()
            ->assertSee('name="offline-pack"', false);

        // the bar works for everyone; there is nothing to keep for a visitor
        auth()->logout();
        $this->get(route('coaches.index'))->assertOk()->assertSee('js/offline.js', false)->assertDontSee('name="offline-pack"', false);
    }

    public function test_the_files_the_app_keeps_first_exist(): void
    {
        preg_match('/const PRECACHE = \[(.*?)\];/s', file_get_contents(public_path('sw.js')), $list);
        preg_match_all("/'([^']+)'/", $list[1], $paths);

        $this->assertNotEmpty($paths[1]);
        foreach ([...$paths[1], '/offline.html'] as $path) {
            $this->assertFileExists(public_path(ltrim($path, '/')), $path);
        }
    }
}
