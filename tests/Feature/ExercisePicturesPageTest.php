<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\User;
use App\Support\ExercisePicture;
use App\Support\PicturePrompt;
use Database\Seeders\ExercisesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/** The admin makes each exercise's pictures with Gemini: copy the text, upload the two pictures. */
class ExercisePicturesPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // settings are kept in memory per process: start from a clean database's settings
        (new \ReflectionProperty(\App\Models\Setting::class, 'cache'))->setValue(null, null);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path(ExercisePicture::DIR));
        parent::tearDown();
    }

    public function test_every_exercise_gets_a_text_with_its_positions_and_muscles(): void
    {
        $this->seed(ExercisesSeeder::class);

        foreach (Exercise::all() as $exercise) {
            $start = PicturePrompt::start($exercise);
            $this->assertStringContainsString('Start position: ', $start, $exercise->name);
            $this->assertStringContainsString('Highlighted in bright red: the ', $start, $exercise->name);
            $this->assertStringNotContainsString(' your ', $start, $exercise->name);
            // the end is a full description of its own, in the same style
            $this->assertStringStartsWith('A new picture, not the previous one. Clean anatomical fitness illustration', PicturePrompt::end($exercise));
            $this->assertStringContainsString('End position of the movement: ', PicturePrompt::end($exercise));
            $this->assertStringContainsString('Highlighted in bright red: the ', PicturePrompt::end($exercise));
        }

        $squat = Exercise::where('name', 'Barbell Back Squat')->sole();
        $this->assertStringContainsString('quadriceps (front of the thighs) and glutes', PicturePrompt::start($squat));
        $this->assertStringContainsString('Bottom of the squat', PicturePrompt::end($squat));

        // image models mix up incline and decline: both texts say where the head is
        $decline = Exercise::where('name', 'Decline Dumbbell Bench Press')->sole();
        $this->assertStringContainsString('his head is clearly LOWER than his hips', PicturePrompt::start($decline));
        $this->assertStringContainsString('his head is clearly LOWER than his hips', PicturePrompt::end($decline));
        $this->assertStringContainsString('seen exactly from the side', PicturePrompt::start($decline));
        $this->assertStringContainsString('seen from a 3/4 side angle', PicturePrompt::start($squat = Exercise::where('name', 'Barbell Biceps Curl')->sole()));
        $this->assertStringContainsString('clearly HIGHER than his hips', PicturePrompt::start(Exercise::where('name', 'Incline Dumbbell Press')->sole()));
    }

    public function test_the_admin_uploads_the_two_pictures(): void
    {
        $this->seed(ExercisesSeeder::class);
        $admin = User::factory()->admin()->create();
        $fly = Exercise::where('name', 'Cable Chest Fly')->sole();

        $this->actingAs($admin)->get(route('admin.exercises.pictures'))->assertOk()
            ->assertSee('Cable Chest Fly')->assertSee('data-copy-text', false)
            ->assertSee(e(PicturePrompt::start($fly)), false);

        // the first time both pictures are needed
        $this->actingAs($admin)->postJson(route('admin.exercises.pictures.store', $fly->id), [
            'start' => UploadedFile::fake()->image('start.png', 1024, 1024),
        ])->assertStatus(422)->assertJsonPath('errors.end.0', 'اختر صورة النهاية.');

        $this->actingAs($admin)->postJson(route('admin.exercises.pictures.store', $fly->id), [
            'start' => UploadedFile::fake()->image('start.png', 1024, 1024),
            'end'   => UploadedFile::fake()->image('end.png', 1024, 1024),
        ])->assertOk()->assertJsonStructure(['redirect']);

        $fly->refresh();
        $this->assertTrue($fly->has_own_pictures);
        $this->assertCount(2, $fly->frames);
        foreach ($fly->frames as $path) {
            $this->assertFileExists(public_path($path));
            $this->assertStringEndsWith('.webp', $path);
            $this->assertLessThanOrEqual(900, getimagesize(public_path($path))[0]);
        }
        $this->assertSame(asset(ExercisePicture::DIR . '/thumbs/' . basename($fly->frames[0])), $fly->thumbnail);
        $this->actingAs($admin)->get(route('exercises.show', $fly))->assertSee(asset($fly->frames[1]), false)->assertDontSee('Everkinetic');

        // the next deploy keeps them
        $this->seed(ExercisesSeeder::class);
        $this->assertSame($fly->frames, $fly->fresh()->frames);

        // replace only the end picture
        $old = $fly->frames;
        $this->actingAs($admin)->postJson(route('admin.exercises.pictures.store', $fly->id), [
            'end' => UploadedFile::fake()->image('end2.png', 800, 800),
        ])->assertOk();
        $fly->refresh();
        $this->assertSame($old[0], $fly->frames[0]);
        $this->assertNotSame($old[1], $fly->frames[1]);
        $this->assertFileDoesNotExist(public_path($old[1]));

        // and back to the drawing
        $this->actingAs($admin)->delete(route('admin.exercises.pictures.destroy', $fly->id))->assertRedirect();
        $fly->refresh();
        $this->assertFalse($fly->has_own_pictures);
        $this->assertSame((require database_path('seeders/data/exercise_frames.php'))['Cable Chest Fly'], $fly->frames);
    }

    public function test_only_the_admin_sees_the_page(): void
    {
        // others are sent back to their own area, like every admin page
        $this->actingAs(User::factory()->create())->get(route('admin.exercises.pictures'))->assertRedirect()->assertDontSee('Start position');
    }
}
