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

        // the library list shows the drawing too
        $this->get(route('exercises.index'))->assertSee('lib-thumb is-drawing', false)->assertSee(asset($squat->frames[0]), false);
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
            ->assertSee('<video src="' . asset($exercise->video_path) . '"', false);
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
}
