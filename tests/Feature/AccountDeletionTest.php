<?php

namespace Tests\Feature;

use App\Models\CheckIn;
use App\Models\CoachPackage;
use App\Models\CoachPayout;
use App\Models\Conversation;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;
    private User $trainee;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        $this->coach = User::factory()->trainer()->create();
        $this->coach->ensureCoachProfile()->update(['status' => 'approved']);
        $this->trainee = User::factory()->create(['name' => 'Rami Saleh', 'phone' => '0933123456']);
    }

    private function subscription(array $data = []): Subscription
    {
        return Subscription::create($data + [
            'trainee_id' => $this->trainee->id, 'coach_id' => $this->coach->id, 'package_title' => 'Plan', 'price' => 40,
            'coach_amount' => 36, 'duration_days' => 30, 'status' => 'active', 'payment_reference' => 'SC1001',
            'starts_at' => now()->subDays(5), 'ends_at' => now()->addDays(25), 'confirmed_at' => now()->subDays(5),
            'intake' => ['goal' => 'weight_loss', 'health' => ['heart' => false], 'health_notes' => 'Knee pain'],
        ]);
    }

    private function deleteAccount(User $user, array $data = [])
    {
        return $this->actingAs($user)->delete(route('account.destroy'), $data + ['password' => 'password', 'confirm' => '1']);
    }

    public function test_a_client_deletes_their_account_and_the_payment_record_stays(): void
    {
        $sub = $this->subscription(['body_photos' => [$photo = UploadedFile::fake()->image('b.jpg')->store('body_photos', 'local')]]);
        $checkIn = CheckIn::create(['subscription_id' => $sub->id, 'trainee_id' => $this->trainee->id, 'coach_id' => $this->coach->id,
            'weight' => 80, 'photos' => [$checkInPhoto = UploadedFile::fake()->image('c.jpg')->store('check_ins', 'local')]]);
        Conversation::between($this->coach->id, $this->trainee->id)->messages()->create(['sender_id' => $this->trainee->id, 'body' => 'Hi coach']);
        Review::create(['subscription_id' => $sub->id, 'trainee_id' => $this->trainee->id, 'coach_id' => $this->coach->id, 'rating' => 5, 'comment' => 'Great coach']);
        $earned = $this->coach->coachEarnings()['earned'];

        $this->actingAs($this->trainee)->get(route('account.delete'))->assertOk()
            ->assertSee(__('Your current subscription ends now, without a refund.'));

        $this->deleteAccount($this->trainee, ['password' => 'wrong'])->assertSessionHasErrors('password');
        $this->deleteAccount($this->trainee, ['confirm' => ''])->assertSessionHasErrors('confirm');
        $this->assertNull($this->trainee->fresh()->deleted_at);

        $this->deleteAccount($this->trainee)->assertRedirect(route('home'));
        $this->assertGuest();

        $user = $this->trainee->fresh();
        $this->assertNotNull($user->deleted_at);
        $this->assertFalse((bool) $user->is_active);
        $this->assertNotSame('Rami Saleh', $user->name);
        $this->assertNull($user->phone);
        $this->assertStringEndsWith('@deleted.invalid', $user->email);

        // answers, photos, check-ins and chats are gone; the payment is kept
        $sub->refresh();
        $this->assertNull($sub->intake);
        $this->assertNull($sub->body_photos);
        $this->assertTrue($sub->ends_at->lte(now()));
        $this->assertSame('SC1001', $sub->payment_reference);
        Storage::disk('local')->assertMissing($photo);
        Storage::disk('local')->assertMissing($checkInPhoto);
        $this->assertFalse(CheckIn::whereKey($checkIn->id)->exists());
        $this->assertSame(0, Conversation::count());
        $this->assertSame($earned, $this->coach->coachEarnings()['earned']);

        // the review stays, without the name
        $this->get(route('coaches.show', $this->coach))->assertSee('Great coach')->assertSee(__('Former client'))->assertDontSee('Rami');

        // the old email can't log in
        $this->post(route('login'), ['email' => 'deleted', 'password' => 'password']);
        $this->assertGuest();
    }

    public function test_not_while_a_payment_is_being_reviewed(): void
    {
        $this->subscription(['status' => 'payment_review', 'starts_at' => null, 'ends_at' => null]);

        $this->actingAs($this->trainee)->get(route('account.delete'))->assertSee(__('You can\'t delete your account yet:'));
        $this->deleteAccount($this->trainee)->assertSessionHasErrors('account');
        $this->assertNull($this->trainee->fresh()->deleted_at);
    }

    public function test_a_coach_first_finishes_with_clients_and_gets_paid(): void
    {
        $sub = $this->subscription();
        $this->deleteAccount($this->coach)->assertSessionHasErrors('account');   // an active client

        $sub->update(['ends_at' => now()->subDay()]);
        $this->deleteAccount($this->coach)->assertSessionHasErrors('account');   // Himma still owes the coach 36

        CoachPayout::create(['coach_id' => $this->coach->id, 'amount' => 36]);
        $package = CoachPackage::create(['coach_id' => $this->coach->id, 'title' => 'Plan', 'price' => 40, 'duration_days' => 30, 'is_active' => true, 'includes_workout' => true]);
        $this->deleteAccount($this->coach)->assertRedirect(route('home'));

        $this->assertNotNull($this->coach->fresh()->deleted_at);
        $this->assertSame('rejected', $this->coach->fresh()->coachProfile->status);
        $this->assertFalse($package->fresh()->is_active);
        $this->get(route('coaches.show', $this->coach))->assertNotFound();
        $this->assertSame(1, CoachPayout::count(), 'payouts are kept');
    }

    public function test_admins_cannot_use_it(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route('account.delete'))->assertRedirect(route('admin.dashboard'));
    }
}
