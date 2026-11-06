<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\User;
use Database\Seeders\ExercisesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** Every exercise shows its movement (drawings or Himma's own video) and the muscles it works. No YouTube. */
class ExerciseMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        foreach (Exercise::whereNotNull('video_path')->pluck('video_path') as $path) {
            File::delete(public_path($path));
        }
        parent::tearDown();
    }

    public function test_the_library_gets_drawings_and_muscles_and_keeps_admin_changes(): void
    {
        $this->seed(ExercisesSeeder::class);

        $bench = Exercise::where('name', 'Barbell Bench Press')->sole();
        $this->assertCount(2, $bench->frames);
        foreach ($bench->frames as $frame) {
            $this->assertFileExists(public_path($frame));
        }
        $this->assertSame(['primary' => ['chest'], 'secondary' => ['shoulders', 'triceps']], $bench->muscles);
        $this->assertGreaterThan(100, Exercise::whereNotNull('frames')->count());

        // every drawing is there, and each exercise has a small thumbnail for the lists
        foreach (require database_path('seeders/data/exercise_frames.php') as $name => $files) {
            foreach ($files as $file) {
                $this->assertFileExists(public_path($file), $name);
            }
            $this->assertFileExists(public_path(str_replace('images/exercises/', 'images/exercises/thumbs/', $files[0])), $name);
        }
        $this->assertSame(0, Exercise::whereNull('primary_muscles')->count());

        // the next deploy runs the seeder again: what the admin chose stays
        $bench->update(['primary_muscles' => ['triceps'], 'frames' => ['images/exercises/push-up-1.webp']]);
        $this->seed(ExercisesSeeder::class);
        $bench->refresh();
        $this->assertSame(['triceps'], $bench->primary_muscles);
        $this->assertSame(['images/exercises/push-up-1.webp'], $bench->frames);
    }

    public function test_the_exercise_page_shows_the_movement_and_colors_the_muscles(): void
    {
        $this->seed(ExercisesSeeder::class);
        $squat = Exercise::where('name', 'Barbell Back Squat')->sole();
        $squat->update(['video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);

        $page = $this->actingAs(User::factory()->create())->get(route('exercises.show', $squat))->assertOk()
            ->assertSee('data-demo', false)
            ->assertSee(asset($squat->frames[0]), false)
            ->assertSee('Everkinetic')
            ->assertSee('class="mm-part mm-on" data-muscle="quads"', false)
            ->assertSee('class="mm-part mm-soft" data-muscle="hamstrings"', false)
            ->assertSee('class="mm-part " data-muscle="chest"', false)
            ->assertSee('العضلات المستهدفة');

        $html = $page->getContent();
        $this->assertStringNotContainsStringIgnoringCase('youtube', $html);
        $this->assertStringNotContainsString('<iframe', $html);

        // the library list shows the drawing too, as a small thumbnail so the list loads fast
        $thumb = str_replace('images/exercises/', 'images/exercises/thumbs/', $squat->frames[0]);
        $this->assertFileExists(public_path($thumb));
        $this->get(route('exercises.index'))->assertSee('lib-thumb is-drawing', false)->assertSee(asset($thumb), false);
    }

    public function test_the_admin_uploads_a_video_that_plays_on_the_platform(): void
    {
        $admin = User::factory()->admin()->create();
        $exercise = Exercise::create(['name' => 'Test Row', 'muscle_group' => 'back', 'difficulty' => 'beginner', 'is_active' => true]);

        $this->actingAs($admin)->postJson(route('admin.exercises.video', $exercise->id), [
            'video_file' => UploadedFile::fake()->create('row.txt', 10, 'text/plain'),
        ])->assertStatus(422)->assertJsonPath('errors.video_file.0', 'اختر ملف فيديو: MP4، أو MOV من الآيفون.');

        $this->actingAs($admin)->postJson(route('admin.exercises.video', $exercise->id), [
            'video_file' => UploadedFile::fake()->create('row.mov', 900, 'video/quicktime'),
        ], ['Referer' => route('admin.exercises.index')])->assertOk()->assertJsonPath('redirect', route('admin.exercises.index'));

        $exercise->refresh();
        $this->assertStringEndsWith('.mov', $exercise->video_path);
        $this->assertFileExists(public_path($exercise->video_path));
        $this->assertTrue($exercise->has_video);

        $this->actingAs(User::factory()->create())->get(route('exercises.show', $exercise))
            ->assertSee('<video src="' . asset($exercise->video_path) . '" autoplay muted loop playsinline', false);
    }

    public function test_the_admin_picks_the_muscles(): void
    {
        $admin = User::factory()->admin()->create();
        $exercise = Exercise::create(['name' => 'Test Press', 'muscle_group' => 'shoulders', 'difficulty' => 'beginner', 'is_active' => true]);

        // without a choice the muscle group is used
        $this->assertSame(['primary' => ['shoulders'], 'secondary' => []], $exercise->muscles);

        $this->actingAs($admin)->put(route('admin.exercises.update', $exercise->id), [
            'name' => 'Test Press', 'muscle_group' => 'shoulders', 'difficulty' => 'beginner', 'is_active' => '1',
            'primary_muscles' => ['shoulders'], 'secondary_muscles' => ['triceps', 'shoulders', 'traps'],
        ])->assertRedirect(route('admin.exercises.index'));

        $this->assertSame(['primary' => ['shoulders'], 'secondary' => ['triceps', 'traps']], $exercise->fresh()->muscles);

        $this->actingAs($admin)->put(route('admin.exercises.update', $exercise->id), [
            'name' => 'Test Press', 'muscle_group' => 'shoulders', 'difficulty' => 'beginner', 'primary_muscles' => ['wings'],
        ])->assertSessionHasErrors('primary_muscles.0');
    }

    public function test_himmas_own_pictures_replace_the_drawing_without_the_drawing_credit(): void
    {
        foreach (require database_path('seeders/data/exercise_pictures.php') as $name => $files) {
            foreach ($files as $file) {
                $this->assertFileExists(public_path($file), $name);
            }
            $this->assertFileExists(public_path(str_replace('images/exercises/', 'images/exercises/thumbs/', $files[0])), $name);
        }

        // an install that already has the old drawing gets the new pictures on the next deploy
        $this->seed(ExercisesSeeder::class);
        $bench = Exercise::where('name', 'Barbell Bench Press')->sole();
        $old = (require database_path('seeders/data/exercise_frames.php'))['Barbell Bench Press'];
        $bench->update(['frames' => $old]);
        $this->seed(ExercisesSeeder::class);
        $bench->refresh();
        $this->assertSame('images/exercises/ai/barbell-bench-press-1.webp', $bench->frames[0]);

        $this->actingAs(User::factory()->create())->get(route('exercises.show', $bench))->assertOk()
            ->assertSee(asset('images/exercises/ai/barbell-bench-press-2.webp'), false)
            ->assertDontSee('Everkinetic');
        $this->get(route('exercises.show', Exercise::where('name', 'Leg Press')->sole()))->assertSee('Everkinetic');
    }

    public function test_the_video_cover_comes_from_the_video_and_becomes_the_exercise_picture(): void
    {
        $this->seed(ExercisesSeeder::class);
        $admin = User::factory()->admin()->create();
        $fly = Exercise::where('name', 'Dumbbell Fly')->sole();
        $send = fn (Exercise $e, string $poster) => $this->actingAs($admin)->postJson(route('admin.exercises.video', $e->id), [
            'video_file' => UploadedFile::fake()->create('fly.mp4', 900, 'video/mp4'),
            'poster'     => UploadedFile::fake()->image($poster, 1280, 720),
        ])->assertOk();

        // the drawing gives way to the frame the browser took from the video
        $send($fly, 'cover.jpg');
        $fly->refresh();
        $this->assertTrue($fly->has_video);
        $this->assertCount(1, $fly->frames);
        $this->assertStringContainsString('-cover-', $fly->frames[0]);
        $this->assertFileExists(public_path($fly->frames[0]));
        $this->assertStringContainsString('/thumbs/', $fly->thumbnail);
        $this->actingAs($admin)->get(route('exercises.show', $fly))->assertSee('poster="' . asset($fly->frames[0]) . '"', false);

        // a new video replaces its cover
        $old = $fly->frames[0];
        $send($fly, 'cover2.jpg');
        $fly->refresh();
        $this->assertNotSame($old, $fly->frames[0]);
        $this->assertFileDoesNotExist(public_path($old));

        // pictures the admin made stay, the cover isn't used
        $press = Exercise::where('name', 'Barbell Bench Press')->sole();
        $pictures = $press->frames;
        $send($press, 'cover.jpg');
        $this->assertSame($pictures, $press->fresh()->frames);

        // the page counts an exercise with a video as done
        $this->actingAs($admin)->get(route('admin.exercises.pictures', ['show' => 'done']))->assertSee('Dumbbell Fly');
    }
}

