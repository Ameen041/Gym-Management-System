<?php

namespace Tests\Feature;

use App\Models\CoachPayout;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PayoutAccountTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->admin = User::factory()->admin()->create();
        $this->coach = User::factory()->trainer()->create(['phone' => '0944000001']);
        $this->coach->ensureCoachProfile()->update(['status' => 'approved', 'bio' => str_repeat('Coach bio. ', 5), 'specialties' => ['strength']]);
    }

    private function save(array $data)
    {
        return $this->actingAs($this->coach)->post(route('coach.profile.payout'), $data + [
            'shamcash_name' => 'Ahmad Coach', 'shamcash_account' => '0933 123 456',
        ]);
    }

    public function test_coach_adds_the_account_without_a_password_the_first_time(): void
    {
        $this->save([])->assertSessionHasNoErrors();

        $profile = $this->coach->coachProfile->fresh();
        $this->assertSame('0933 123 456', $profile->shamcash_account);
        $this->assertSame('•••• 3456', $profile->maskedPayoutAccount());
        $this->assertSame(0, $this->admin->notifications()->count());
    }

    public function test_changing_the_account_needs_the_password_and_tells_the_admin(): void
    {
        $this->save([]);

        $this->save(['shamcash_account' => '0999 888 777'])->assertSessionHasErrors('current_password', null, 'payout');
        $this->save(['shamcash_account' => '0999 888 777', 'current_password' => 'wrong'])->assertSessionHasErrors('current_password', null, 'payout');
        $this->assertSame('0933 123 456', $this->coach->coachProfile->fresh()->shamcash_account);

        $this->save(['shamcash_account' => '0999 888 777', 'current_password' => 'password'])->assertSessionHasNoErrors();
        $this->assertSame('0999 888 777', $this->coach->coachProfile->fresh()->shamcash_account);
        $this->assertSame(1, $this->admin->notifications()->count());
        $this->assertTrue($this->coach->coachProfile->fresh()->payoutAccountChangedRecently());
    }

    public function test_qr_image_is_private(): void
    {
        $this->save(['shamcash_qr' => UploadedFile::fake()->image('qr.png')]);
        $url = route('coaches.payout-qr', $this->coach);

        $this->actingAs($this->coach)->get($url)->assertOk();
        $this->actingAs($this->admin)->get($url)->assertOk();
        $this->actingAs(User::factory()->trainer()->create())->get($url)->assertForbidden();
        $this->actingAs(User::factory()->create())->get($url)->assertForbidden();
    }

    public function test_account_is_not_on_the_public_profile(): void
    {
        $this->save([]);

        $this->get(route('coaches.show', $this->coach))->assertDontSee('0933 123 456');
    }

    public function test_admin_sees_the_account_and_each_payout_keeps_it(): void
    {
        $trainee = User::factory()->create();
        Subscription::create([
            'trainee_id' => $trainee->id, 'coach_id' => $this->coach->id, 'package_title' => 'Plan', 'price' => 40,
            'commission_rate' => 10, 'commission_amount' => 4, 'coach_amount' => 36, 'status' => 'active',
            'confirmed_at' => now()->subDays(20), 'starts_at' => now()->subDays(20), 'ends_at' => now()->addDays(10),
        ]);

        $this->actingAs($this->admin)->get(route('admin.payouts.index'))->assertSee(__('No account yet'));

        $this->save([]);
        $this->actingAs($this->admin)->get(route('admin.payouts.index'))
            ->assertSee('0933 123 456')->assertSee('Ahmad Coach');

        $this->actingAs($this->admin)->post(route('admin.payouts.store'), ['coach_id' => $this->coach->id, 'amount' => 10]);
        $this->assertSame('Ahmad Coach · 0933 123 456', CoachPayout::sole()->account);

        // a later change does not rewrite where past money went
        $this->save(['shamcash_account' => '0999 888 777', 'current_password' => 'password']);
        $this->assertSame('Ahmad Coach · 0933 123 456', CoachPayout::sole()->fresh()->account);
    }
}
