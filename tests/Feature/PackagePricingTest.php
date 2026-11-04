<?php

namespace Tests\Feature;

use App\Models\CoachPackage;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The admin sets the price range coaches choose from, and can set and lock any package's price. */
class PackagePricingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $coach;
    private CoachPackage $package;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->coach = User::factory()->trainer()->create();
        $this->coach->ensureCoachProfile()->update(['status' => 'approved']);
        Setting::put('package_min_monthly', 10);
        Setting::put('package_max_monthly', 50);
        $this->package = $this->coach->packages()->create([
            'title' => 'Monthly coaching', 'includes_workout' => true, 'includes_nutrition' => false,
            'price' => 30, 'duration_days' => 30, 'is_active' => true,
        ]);
    }

    private function coachSaves(array $fields)
    {
        return $this->actingAs($this->coach)->put(route('coach.packages.update', $this->package), $fields + [
            'title' => 'Monthly coaching', 'includes_workout' => 1, 'is_active' => 1, 'price' => 30, 'duration_days' => 30,
        ]);
    }

    private function adminSaves(array $fields)
    {
        return $this->actingAs($this->admin)->put(route('admin.packages.update', $this->package), $fields + [
            'price' => 30, 'is_active' => 1, 'price_locked' => 0,
        ]);
    }

    public function test_coaches_choose_a_price_inside_the_range(): void
    {
        $this->coachSaves(['price' => 60])->assertSessionHasErrors('price');
        $this->coachSaves(['price' => 8])->assertSessionHasErrors('price');
        $this->assertSame(30.0, $this->package->fresh()->price);

        // a 90-day package may cost three times the monthly range
        $this->coachSaves(['price' => 120, 'duration_days' => 90])->assertSessionHasNoErrors();
        $this->assertSame(120.0, $this->package->fresh()->price);

        $this->actingAs($this->coach)->post(route('coach.packages.store'), [
            'title' => 'Too cheap', 'includes_workout' => 1, 'is_active' => 1, 'price' => 5, 'duration_days' => 30,
        ])->assertSessionHasErrors('price');
    }

    public function test_the_admin_sets_and_locks_a_price_and_the_coach_is_told(): void
    {
        $this->adminSaves(['price' => 45, 'price_locked' => 1])->assertSessionHasNoErrors();
        $this->assertSame(45.0, $this->package->fresh()->price);
        $this->assertTrue($this->package->fresh()->price_locked);
        $this->assertStringContainsString('Only the admin can change it now', $this->coach->notifications()->first()->data['message']);

        // the coach can rename it, but its price and duration stay
        $this->coachSaves(['title' => 'Full coaching', 'price' => 12, 'duration_days' => 90])->assertSessionHasNoErrors();
        $package = $this->package->fresh();
        $this->assertSame(['Full coaching', 45.0, 30], [$package->title, $package->price, $package->duration_days]);
        $this->actingAs($this->coach)->get(route('coach.packages.index'))->assertSee(__('Price set by the admin'));

        // unlocked: the coach sets it again, inside the range
        $this->adminSaves(['price' => 45, 'price_locked' => 0]);
        $this->assertStringContainsString('yourself again', $this->coach->notifications()->get()->pluck('data.message')->implode(' | '));
        $this->coachSaves(['price' => 25])->assertSessionHasNoErrors();
        $this->assertSame(25.0, $this->package->fresh()->price);
    }

    public function test_a_locked_price_may_be_outside_the_range(): void
    {
        $this->adminSaves(['price' => 80, 'price_locked' => 1])->assertSessionHasNoErrors();
        $this->assertSame(80.0, $this->package->fresh()->price);
        $this->assertSame(0, CoachPackage::outOfRange()->count());
    }

    public function test_the_admin_sees_packages_outside_the_range(): void
    {
        $this->coach->packages()->create([
            'title' => 'Old premium', 'includes_workout' => true, 'price' => 90, 'duration_days' => 30, 'is_active' => true,
        ]);

        $this->actingAs($this->admin)->get(route('admin.packages.index'))->assertOk()
            ->assertSee('Monthly coaching')->assertSee('Old premium');
        $this->actingAs($this->admin)->get(route('admin.packages.index', ['out' => 1]))->assertOk()
            ->assertSee('Old premium')->assertDontSee('Monthly coaching');
    }

    public function test_hiding_a_package_tells_the_coach(): void
    {
        $this->adminSaves(['is_active' => 0])->assertSessionHasNoErrors();
        $this->assertFalse($this->package->fresh()->is_active);
        $this->assertStringContainsString('hid your package', $this->coach->notifications()->first()->data['message']);
    }

    public function test_only_the_admin_changes_prices_and_the_range(): void
    {
        $this->actingAs($this->coach)->put(route('admin.packages.update', $this->package), ['price' => 999, 'price_locked' => 0, 'is_active' => 1]);
        $this->assertNotSame(200, $this->actingAs($this->coach)->get(route('admin.packages.index'))->status());
        $this->assertSame(30.0, $this->package->fresh()->price);

        $this->actingAs($this->admin)->post(route('admin.settings.update'), [
            'commission_rate' => 15, 'invite_commission_rate' => 5, 'payout_hold_days' => 7,
            'package_min_monthly' => 40, 'package_max_monthly' => 20,
        ])->assertSessionHasErrors('package_max_monthly');
        $this->assertSame([10.0, 50.0], Setting::packagePriceRange(30));
    }
}
