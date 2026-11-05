<?php

namespace Tests\Feature;

use App\Models\CoachPackage;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Photos from a phone camera are big: the subscribe form takes them, and says clearly what to do when it can't. */
class PhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;
    private User $trainee;
    private CoachPackage $package;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(Subscription::FILES_DISK);
        $this->coach = User::factory()->trainer()->create();
        $this->coach->ensureCoachProfile()->update(['status' => 'approved']);
        $this->package = CoachPackage::create([
            'coach_id' => $this->coach->id, 'title' => 'Full coaching', 'includes_workout' => true, 'includes_nutrition' => true,
            'price' => 40, 'duration_days' => 30, 'is_active' => true,
        ]);
        $this->trainee = User::factory()->create();
    }

    private function subscribe(array $photos)
    {
        return $this->actingAs($this->trainee)->post(route('subscriptions.store', [$this->coach, $this->package]), [
            'goal' => 'weight_loss', 'level' => 'beginner', 'days_per_week' => 4,
            'age' => 25, 'height' => 175, 'weight' => 80, 'phone' => '0933123456',
            'health' => collect(Subscription::HEALTH_QUESTIONS)->map(fn () => '0')->all(), 'consent' => '1',
            'body_photos' => $photos,
        ]);
    }

    public function test_a_6_mb_camera_photo_is_accepted(): void
    {
        $this->subscribe([UploadedFile::fake()->image('front.jpg', 4000, 3000)->size(6 * 1024)])
            ->assertSessionHasNoErrors();

        $sub = Subscription::sole();
        $this->assertCount(1, $sub->body_photos);
        Storage::disk(Subscription::FILES_DISK)->assertExists($sub->body_photos[0]);
    }

    public function test_too_big_or_unknown_photos_say_what_to_do(): void
    {
        $this->subscribe([UploadedFile::fake()->image('front.jpg')->size(11 * 1024)])
            ->assertSessionHasErrors(['body_photos.0' => 'حجم الصورة أكبر من 10 ميغابايت. اختر صورة أصغر، أو خذ لقطة شاشة للصورة وارفع اللقطة.']);

        $this->subscribe([UploadedFile::fake()->create('front.heic', 500, 'image/heic')])
            ->assertSessionHasErrors(['body_photos.0' => 'يجب أن تكون الصورة بصيغة JPG أو PNG أو WEBP. خذ لقطة شاشة للصورة وارفع اللقطة.']);

        $this->assertSame(0, Subscription::count());
    }

    public function test_the_form_shrinks_photos_and_shows_it_is_sending(): void
    {
        $this->actingAs($this->trainee)->get(route('subscriptions.create', [$this->coach, $this->package]))
            ->assertOk()
            ->assertSee('name="body_photos[]" accept="image/*" multiple data-shrink', false)
            ->assertSee('data-busy="جارٍ الإرسال… لا تغلق الصفحة"', false);
    }
}
