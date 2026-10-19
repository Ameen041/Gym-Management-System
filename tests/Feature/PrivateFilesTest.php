<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateFilesTest extends TestCase
{
    use RefreshDatabase;

    private User $trainee;
    private User $coach;
    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');

        $this->trainee = User::factory()->create();
        $this->coach = User::factory()->trainer()->create();
        Storage::disk('local')->put('payments/receipt.png', 'receipt');
        Storage::disk('local')->put('body_photos/front.png', 'photo');

        $this->subscription = Subscription::create([
            'trainee_id' => $this->trainee->id, 'coach_id' => $this->coach->id,
            'package_title' => 'Plan', 'price' => 25, 'status' => 'payment_review',
            'payment_proof' => 'payments/receipt.png', 'body_photos' => ['body_photos/front.png'],
        ]);
    }

    public function test_trainee_sees_own_receipt_and_photos(): void
    {
        $this->actingAs($this->trainee)->get($this->subscription->proofUrl())->assertOk();
        $this->actingAs($this->trainee)->get($this->subscription->photoUrls()[0])->assertOk();
    }

    public function test_coach_sees_photos_but_not_the_receipt(): void
    {
        $this->actingAs($this->coach)->get($this->subscription->photoUrls()[0])->assertOk();
        $this->actingAs($this->coach)->get($this->subscription->proofUrl())->assertForbidden();
    }

    public function test_admin_sees_everything(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get($this->subscription->proofUrl())->assertOk();
        $this->actingAs($admin)->get($this->subscription->photoUrls()[0])->assertOk();
    }

    public function test_others_and_guests_are_kept_out(): void
    {
        $stranger = User::factory()->create();
        $otherCoach = User::factory()->trainer()->create();

        $this->actingAs($stranger)->get($this->subscription->proofUrl())->assertForbidden();
        $this->actingAs($otherCoach)->get($this->subscription->photoUrls()[0])->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->get($this->subscription->proofUrl())->assertRedirect();
    }

    public function test_missing_photo_index_is_not_found(): void
    {
        $this->actingAs($this->trainee)->get(route('subscriptions.file', [$this->subscription, 'photo', 5]))->assertNotFound();
    }

    public function test_new_receipt_is_stored_off_the_public_disk(): void
    {
        $this->subscription->update(['status' => 'awaiting_payment', 'payment_proof' => null]);

        $this->actingAs($this->trainee)
            ->post(route('subscriptions.pay.submit', $this->subscription), [
                'payment_proof'     => UploadedFile::fake()->image('receipt.jpg'),
                'payment_reference' => 'SC-123456',
            ])
            ->assertRedirect(route('trainee.dashboard'));

        $path = $this->subscription->fresh()->payment_proof;
        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }
}
