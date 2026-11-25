<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Support\FormNote;
use Database\Seeders\ExercisesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The pictures get the movement right but not always the hands: the grip, one arm or one leg after the other is
 * written out under the picture, on the exercise page and in the trainee's plan.
 */
class FormNoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_note_belongs_to_an_exercise_of_the_library(): void
    {
        $this->seed(ExercisesSeeder::class);

        $this->assertSame([], array_values(array_diff(FormNote::names(), Exercise::pluck('name')->all())));
    }

    public function test_the_grips_are_named_with_the_words_of_the_gym(): void
    {
        $label = fn (string $name) => FormNote::for(new Exercise(['name' => $name]))[0]['label'];

        app()->setLocale('ar');
        $this->assertSame('قبضة عادية', $label('Pull-Up'));
        $this->assertSame('قبضة عكسية', $label('Chin-Up'));
        $this->assertSame('قبضة واسعة', $label('Lat Pulldown'));
        $this->assertSame('قبضة ضيقة', $label('Close-Grip Lat Pulldown'));
        $this->assertSame('قبضة عادية', $label('Barbell Biceps Curl'));
        $this->assertSame('قبضة عكسية', $label('Reverse-Grip Barbell Curl'));
        $this->assertSame('قبضة متقابلة', $label('Hammer Curl'));
        $this->assertSame('قبضة متقابلة', $label('V-Bar Lat Pulldown'));
        // the book words people don't use at the gym
        foreach (FormNote::names() as $name) {
            $this->assertDoesNotMatchRegularExpression('/علوية|سفلية|هامر|المطرقة/u', $label($name), $name);
        }
    }

    public function test_the_exercise_page_writes_out_the_grip_under_the_picture(): void
    {
        $this->seed(ExercisesSeeder::class);
        $user = User::factory()->create();
        $chinUp = Exercise::where('name', 'Chin-Up')->sole();

        $this->actingAs($user)->get(route('exercises.show', $chinUp))->assertOk()
            ->assertSee('class="ex-note"', false)
            ->assertSee('fa-hand', false)
            ->assertSee('قبضة عكسية')
            ->assertSee('باطن الكفين باتجاه وجهك، واليدان بعرض الكتفين.');

        $this->withSession(['locale' => 'en'])->get(route('exercises.show', $chinUp))
            ->assertSee('Reverse grip')
            ->assertSee('Palms facing you, hands shoulder-width apart.');

        $this->withSession(['locale' => 'ar'])->get(route('exercises.show', Exercise::where('name', 'One-Arm Dumbbell Row')->sole()))
            ->assertSee('يد واحدة')
            ->assertSee('أكمل المجموعة ثم بدّل اليد.');

        // nothing to add under the picture of a squat
        $this->get(route('exercises.show', Exercise::where('name', 'Barbell Back Squat')->sole()))->assertOk()
            ->assertDontSee('class="ex-note"', false);
    }

    public function test_the_trainee_plan_shows_the_grip_next_to_the_exercise(): void
    {
        $this->seed(ExercisesSeeder::class);
        $trainee = User::factory()->create();
        $coach = User::factory()->trainer()->create();
        $sub = Subscription::create([
            'trainee_id' => $trainee->id, 'coach_id' => $coach->id, 'package_title' => 'Plan', 'price' => 20,
            'includes_workout' => true, 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(29),
        ]);
        $curl = Exercise::where('name', 'Alternating Dumbbell Curl')->sole();
        $squat = Exercise::where('name', 'Barbell Back Squat')->sole();
        WorkoutPlan::create([
            'user_id' => $trainee->id, 'trainer_id' => $coach->id, 'subscription_id' => $sub->id, 'title' => 'Plan',
            'plan_details' => json_encode([now()->format('l') => [
                ['muscle' => 'Biceps', 'exercise' => $curl->name, 'exercise_id' => $curl->id, 'sets' => '3', 'reps' => '12'],
                ['muscle' => 'Legs', 'exercise' => $squat->name, 'exercise_id' => $squat->id, 'sets' => '4', 'reps' => '8'],
            ]]),
        ]);

        $page = $this->actingAs($trainee)->get(route('trainee.schedule', ['subscription' => $sub->id]))->assertOk()
            ->assertSee('class="ex-note-chip"', false)
            ->assertSee('fa-right-left', false)
            ->assertSee('بالتناوب');
        $this->assertSame(1, substr_count($page->getContent(), 'class="ex-note-chip"'));
    }

    public function test_the_pictures_page_says_the_grip_is_written_under_the_picture(): void
    {
        $this->seed(ExercisesSeeder::class);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.exercises.pictures', ['show' => 'all', 'q' => 'chin-up']))->assertOk()
            ->assertSee('class="pic-note"', false)
            ->assertSee('قبضة عكسية')
            ->assertSee(__('So the hands in the picture do not have to be exact: if the movement is right, save it.'));

        $this->actingAs($admin)->get(route('admin.exercises.pictures', ['show' => 'all', 'q' => 'back squat']))->assertOk()
            ->assertDontSee('class="pic-note"', false);
    }
}
