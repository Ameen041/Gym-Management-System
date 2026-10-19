<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentReferenceTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->coach = User::factory()->trainer()->create();
    }

    private function pay(User $trainee, ?string $reference)
    {
        $sub = Subscription::create([
            'trainee_id' => $trainee->id, 'coach_id' => $this->coach->id, 'package_title' => 'Plan', 'price' => 40,
            'duration_days' => 30, 'status' => 'awaiting_payment',
        ]);

        return [$sub, $this->actingAs($trainee)->post(route('subscriptions.pay.submit', $sub), array_filter([
            'payment_proof' => UploadedFile::fake()->image('receipt.jpg'), 'payment_reference' => $reference,
        ]))];
    }

    public function test_the_number_is_required_and_kept_in_one_spelling(): void
    {
        [$sub, $response] = $this->pay(User::factory()->create(), null);
        $response->assertSessionHasErrors('payment_reference');
        $this->assertSame('awaiting_payment', $sub->fresh()->status);

        [$sub, $response] = $this->pay(User::factory()->create(), ' ٨٨١٢ 34-ab ');
        $response->assertRedirect(route('trainee.dashboard'));
        $this->assertSame('881234AB', $sub->fresh()->payment_reference);
    }

    public function test_a_number_can_pay_for_one_subscription_only(): void
    {
        $admin = User::factory()->admin()->create();
        $this->pay(User::factory()->create(), '88123456');

        [$sub, $response] = $this->pay(User::factory()->create(), '8812 3456');
        $response->assertSessionHasErrors('payment_reference');
        $this->assertSame('awaiting_payment', $sub->fresh()->status);
        $this->assertTrue($admin->notifications->contains(fn ($n) => str_contains($n->data['message'], 'already used')));
    }

    public function test_the_same_subscription_can_send_its_number_again_after_a_rejection(): void
    {
        [$sub] = $this->pay($trainee = User::factory()->create(), '88123456');
        $sub->update(['status' => 'rejected']);

        $this->actingAs($trainee)->post(route('subscriptions.pay.submit', $sub), [
            'payment_proof' => UploadedFile::fake()->image('clearer.jpg'), 'payment_reference' => '88123456',
        ])->assertRedirect(route('trainee.dashboard'));
        $this->assertSame('payment_review', $sub->fresh()->status);
    }
}
