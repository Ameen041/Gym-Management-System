<?php

namespace Tests\Feature;

use App\Http\Controllers\CoachDirectoryController;
use App\Models\CoachPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Approving a coach who has no package yet: nobody is told they are on the coaches page. */
class CoachApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_an_incomplete_coach_says_what_is_still_missing(): void
    {
        $admin = User::factory()->admin()->create();
        $coach = User::factory()->trainer()->create();
        $coach->ensureCoachProfile();

        $this->actingAs($admin)->post(route('admin.coaches.approve', $coach))
            ->assertSessionHas('status', fn ($status) => str_contains($status, __('At least one package')));

        $note = $coach->notifications()->sole()->data;
        $this->assertStringContainsString('To appear on the coaches page', $note['message']);
        $this->assertStringContainsString(__('At least one package'), $note['params']['missing']);
        $this->assertFalse(CoachDirectoryController::listedCoaches()->contains('id', $coach->id));

        // with a package they are on the coaches page
        CoachPackage::create(['coach_id' => $coach->id, 'title' => 'Monthly', 'price' => 30, 'duration_days' => 30,
            'includes_workout' => true, 'includes_nutrition' => true, 'is_active' => true]);
        $this->assertTrue(CoachDirectoryController::listedCoaches()->contains('id', $coach->id));
    }
}
