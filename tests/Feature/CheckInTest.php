<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    use RefreshDatabase;

    private User $trainee;
    private User $coach;
    private Subscription $sub;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(9, 0));

        $this->trainee = User::factory()->create(['weight' => 90]);
        $this->coach = User::factory()->trainer()->create();
        $this->sub = Subscription::create([
            'trainee_id' => $this->trainee->id, 'coach_id' => $this->coach->id, 'package_title' => 'Workout only',
            'includes_workout' => true, 'includes_nutrition' => false, 'duration_days' => 60, 'price' => 25,
            'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addDays(60),
            'intake' => ['goal' => 'weight_loss', 'weight' => 90],
        ]);
    }

    private function send(array $data = [])
    {
        return $this->actingAs($this->trainee)->post(route('check-ins.store', $this->sub), $data + [
            'weight' => 89.2, 'workouts_done' => 4, 'diet_adherence' => 5, 'energy' => 4, 'sleep' => 3, 'note' => 'Good week',
        ]);
    }

    public function test_first_check_in_opens_six_days_after_the_start(): void
    {
        $this->send()->assertSessionHas('error');
        $this->assertSame(0, CheckIn::count());

        $this->travel(6)->days();
        $this->actingAs($this->trainee)->get(route('check-ins.show', $this->sub))->assertOk()->assertSee(__('Send check-in'));
        $this->send()->assertRedirect(route('check-ins.show', $this->sub));

        $checkIn = CheckIn::sole();
        $this->assertSame(89.2, $checkIn->weight);
        $this->assertSame(4, $checkIn->workouts_done);
        $this->assertNull($checkIn->diet_adherence, 'diet question is dropped for a workout-only package');
        $this->assertEquals(89.2, $this->trainee->fresh()->weight);
        $this->assertSame(1, $this->coach->notifications()->count());
    }

    public function test_only_one_check_in_a_week(): void
    {
        $this->travel(7)->days();
        $this->send()->assertSessionDoesntHaveErrors();
        $this->send(['weight' => 88])->assertSessionHas('error');
        $this->assertSame(1, CheckIn::count());

        $this->travel(6)->days();
        $this->send(['weight' => 88.5])->assertSessionDoesntHaveErrors();
        $this->assertSame(2, CheckIn::count());
    }

    public function test_coach_replies_and_trainee_is_told_once(): void
    {
        $this->travel(7)->days();
        $this->send();
        $checkIn = CheckIn::sole();

        $this->actingAs($this->coach)->get(route('coach.clients.show', $this->sub))
            ->assertOk()->assertSee('89.2')->assertSee(__('Needs a reply'));
        $this->actingAs($this->coach)->get(route('trainer.dashboard'))->assertSee(__('New check-in'));

        $this->actingAs($this->coach)->post(route('coach.check-ins.reply', $checkIn), ['coach_reply' => 'Nice work'])
            ->assertRedirect();
        $this->actingAs($this->coach)->post(route('coach.check-ins.reply', $checkIn), ['coach_reply' => 'Nice work, keep going']);

        $this->assertSame('Nice work, keep going', $checkIn->fresh()->coach_reply);
        $this->assertSame(1, $this->trainee->notifications()->count());
        $this->actingAs($this->trainee)->get(route('check-ins.show', $this->sub))->assertSee('Nice work, keep going');
    }

    public function test_others_cannot_reply_or_see(): void
    {
        $this->travel(7)->days();
        $this->send();
        $checkIn = CheckIn::sole();
        $otherCoach = User::factory()->trainer()->create();
        $otherTrainee = User::factory()->create();

        $this->actingAs($otherCoach)->post(route('coach.check-ins.reply', $checkIn), ['coach_reply' => 'x'])->assertNotFound();
        $this->actingAs($otherTrainee)->get(route('check-ins.show', $this->sub))->assertNotFound();
        $this->actingAs($otherTrainee)->post(route('check-ins.store', $this->sub), ['weight' => 80])->assertNotFound();
        $this->assertNull($checkIn->fresh()->coach_reply);
    }

    public function test_photos_are_private(): void
    {
        $this->travel(7)->days();
        $this->send(['photos' => [UploadedFile::fake()->image('front.jpg')]]);
        $checkIn = CheckIn::sole();
        Storage::disk('local')->assertExists($checkIn->photos[0]);
        $url = $checkIn->photoUrls()[0];

        $this->actingAs($this->trainee)->get($url)->assertOk();
        $this->actingAs($this->coach)->get($url)->assertOk();
        $this->actingAs(User::factory()->admin()->create())->get($url)->assertOk();
        $this->actingAs(User::factory()->trainer()->create())->get($url)->assertForbidden();
    }

    public function test_reminders_go_out_once_and_coach_hears_after_three_late_days(): void
    {
        $this->travel(6)->days();
        $this->artisan('app:check-in-reminders');
        $this->assertSame(0, $this->trainee->notifications()->count(), 'not due yet');

        $this->travel(1)->days();
        $this->artisan('app:check-in-reminders');
        $this->artisan('app:check-in-reminders');
        $this->assertSame(1, $this->trainee->notifications()->count());
        $this->assertSame(0, $this->coach->notifications()->count());

        $this->travel(3)->days();
        $this->artisan('app:check-in-reminders');
        $this->assertSame(1, $this->trainee->notifications()->count());
        $this->assertSame(1, $this->coach->notifications()->count());

        // after a check-in the next reminder waits a week
        $this->send();
        $this->artisan('app:check-in-reminders');
        $this->assertSame(1, $this->trainee->notifications()->count());
    }

    public function test_chart_starts_at_the_intake_weight(): void
    {
        $this->travel(7)->days();
        $this->send(['weight' => 89]);

        $points = \App\Http\Controllers\CheckInController::chartPoints($this->sub, CheckIn::all());
        $this->assertSame([90.0, 89.0], array_column($points, 'weight'));
        $this->actingAs($this->trainee)->get(route('check-ins.show', $this->sub))->assertSee('data-chart', false);
    }
}
