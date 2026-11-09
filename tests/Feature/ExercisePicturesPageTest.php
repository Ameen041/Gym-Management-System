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
            ->assertSee(e(PicturePrompt::start($fly)), false)
            // step 4: the text that makes Gemini animate the picture, step 5: the video upload
            ->assertSee(e(PicturePrompt::video($fly)), false)
            ->assertSee(route('admin.exercises.video', $fly->id), false);
        $this->assertStringStartsWith('Animate this image into a short video', PicturePrompt::video($fly));
        $this->assertStringContainsString('Arms opened wide in an arc', PicturePrompt::video(Exercise::where('name', 'Dumbbell Fly')->sole()));

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

    public function test_one_picture_is_changed_or_both_swapped_and_exercises_can_be_searched(): void
    {
        $this->seed(ExercisesSeeder::class);
        $admin = User::factory()->admin()->create();
        $fly = Exercise::where('name', 'Cable Chest Fly')->sole();
        $this->actingAs($admin)->postJson(route('admin.exercises.pictures.store', $fly->id), [
            'start' => UploadedFile::fake()->image('start.png', 900, 900),
            'end'   => UploadedFile::fake()->image('end.png', 900, 900),
        ])->assertOk();
        $fly->refresh();
        [$start, $end] = $fly->frames;

        // the page shows each picture with its own "change", and the swap button
        $this->actingAs($admin)->get(route('admin.exercises.pictures', ['show' => 'done', 'q' => 'cable chest']))->assertOk()
            ->assertSee('Cable Chest Fly')->assertDontSee('Barbell Bench Press')
            ->assertSee('name="start" accept="image/*" hidden data-shrink="1200" data-autosubmit', false)
            ->assertSee('name="end" accept="image/*" hidden data-shrink="1200" data-autosubmit', false)
            ->assertSee(route('admin.exercises.pictures.swap', $fly->id), false);
        // searching in Arabic works too
        $this->actingAs($admin)->get(route('admin.exercises.pictures', ['show' => 'all', 'q' => 'بالكابل']))->assertSee('Cable Chest Fly');

        // swap
        $this->actingAs($admin)->post(route('admin.exercises.pictures.swap', $fly->id))->assertRedirect();
        $this->assertSame([$end, $start], $fly->fresh()->frames);

        // change only the start one
        $this->actingAs($admin)->postJson(route('admin.exercises.pictures.store', $fly->id), [
            'start' => UploadedFile::fake()->image('new.png', 900, 900),
        ])->assertOk();
        $frames = $fly->fresh()->frames;
        $this->assertNotSame($end, $frames[0]);
        $this->assertSame($start, $frames[1]);
        $this->assertFileDoesNotExist(public_path($end));

        // an exercise still on its drawing can't be swapped
        $this->actingAs($admin)->post(route('admin.exercises.pictures.swap', Exercise::where('name', 'Leg Press')->value('id')))->assertNotFound();
    }

    public function test_both_pictures_face_the_same_way_and_one_can_be_turned_around(): void
    {
        $this->seed(ExercisesSeeder::class);
        $admin = User::factory()->admin()->create();

        // the same direction in the start and the end text, by the kind of exercise
        $press = Exercise::where('name', 'Overhead Dumbbell Press')->sole();
        foreach ([PicturePrompt::start($press), PicturePrompt::end($press)] as $text) {
            $this->assertStringContainsString('Direction: he is turned toward the RIGHT side of the picture. Keep exactly this direction, do not mirror the picture.', $text);
        }
        $this->assertStringContainsString('his head is on the LEFT side of the picture and his feet are on the RIGHT side', PicturePrompt::end(Exercise::where('name', 'Barbell Bench Press')->sole()));
        $this->assertStringContainsString('we see him from behind', PicturePrompt::start(Exercise::where('name', 'Lat Pulldown')->sole()));

        // a picture that faces the wrong way is mirrored on the server
        $this->actingAs($admin)->postJson(route('admin.exercises.pictures.store', $press->id), [
            'start' => UploadedFile::fake()->image('start.png', 400, 300),
            'end'   => UploadedFile::fake()->image('end.png', 400, 300),
        ])->assertOk();
        $press->refresh();
        [$start, $end] = $press->frames;
        $this->actingAs($admin)->get(route('admin.exercises.pictures', ['show' => 'done', 'q' => 'overhead']))
            ->assertSee(route('admin.exercises.pictures.flip', [$press->id, 1]), false);

        $this->actingAs($admin)->post(route('admin.exercises.pictures.flip', [$press->id, 1]))->assertRedirect()->assertSessionHas('status');
        $press->refresh();
        $this->assertSame($start, $press->frames[0]);
        $this->assertNotSame($end, $press->frames[1]);
        $this->assertFileExists(public_path($press->frames[1]));
        $this->assertFileDoesNotExist(public_path($end));

        // the first picture keeps its list thumbnail when it is turned around
        $this->actingAs($admin)->post(route('admin.exercises.pictures.flip', [$press->id, 0]))->assertRedirect();
        $press->refresh();
        $this->assertStringContainsString('/thumbs/', $press->thumbnail);
        $this->actingAs($admin)->post(route('admin.exercises.pictures.flip', [$press->id, 2]))->assertNotFound();
    }

    public function test_the_end_picture_is_an_edit_of_the_start_picture(): void
    {
        $this->seed(ExercisesSeeder::class);
        $press = Exercise::where('name', 'Overhead Dumbbell Press')->sole();
        $edit = PicturePrompt::edit($press);

        $this->assertStringStartsWith('Edit the attached picture. Change only the pose: show the END position of the movement. ', $edit);
        $this->assertStringContainsString('Press the dumbbells overhead until his arms are straight.', $edit);
        $this->assertStringContainsString('the same grip and hand position, the same equipment, machine and bench', $edit);

        // on the page it is the second step, with the full text kept for when editing doesn't work
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.exercises.pictures', ['q' => 'overhead dumbbell']))
            ->assertSee(e($edit), false)->assertSee('id="pe-' . $press->id . '"', false)
            ->assertSee(e(PicturePrompt::end($press)), false);
    }

    public function test_both_positions_are_asked_for_in_one_picture_that_the_page_cuts_in_two(): void
    {
        $this->seed(ExercisesSeeder::class);
        $press = Exercise::where('name', 'Overhead Dumbbell Press')->sole();
        $pair = PicturePrompt::pair($press);

        $this->assertStringContainsString('ONE wide 16:9 image made of TWO panels side by side', $pair);
        $this->assertStringContainsString('LEFT panel, the start position: Sit on a bench with back support', $pair);
        $this->assertStringContainsString('RIGHT panel, the end position: Press the dumbbells overhead', $pair);
        $this->assertStringContainsString('like two frames of the same video', $pair);
        $this->assertStringContainsString('the same equipment in the same place with exactly the same shape and size', $pair);
        $this->assertStringContainsString('in both panels he is turned toward the RIGHT side of the picture', $pair);

        // the chin-up: drawn from the front with its underhand grip spelled out, in every text
        $chin = Exercise::where('name', 'Chin-Up')->sole();
        foreach ([PicturePrompt::pair($chin), PicturePrompt::start($chin), PicturePrompt::end($chin), PicturePrompt::edit($chin)] as $text) {
            $this->assertStringContainsString('Grip: UNDERHAND (supinated), like holding a barbell for a biceps curl but above his head', $text);
            $this->assertStringContainsString('the BACKS of his hands face the camera, and his thumbs point OUTWARD', $text);
        }
        $this->assertStringContainsString('from a 3/4 front angle', PicturePrompt::pair($chin));
        $this->assertStringNotContainsString('from behind', PicturePrompt::pair($chin));
        $this->assertStringContainsString('until his chin is above the bar', PicturePrompt::pair($chin));
        $this->assertStringContainsString('LEFT panel, the start position: Dead hang: arms COMPLETELY straight', PicturePrompt::pair($chin));

        // step 1 is that text, step 2 the double picture (cut in two in the browser into "start" and "end")
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.exercises.pictures', ['q' => 'overhead dumbbell']))
            ->assertSee(e($pair), false)->assertSee('data-split', false)
            ->assertSee('<input type="file" name="start" accept="image/*" hidden>', false)
            ->assertSee('<input type="file" name="end" accept="image/*" hidden>', false);
    }
}

