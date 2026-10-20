<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The admin lists that run over more than one page. */
class AdminListsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_every_payments_tab_opens_and_pages(): void
    {
        $coach = User::factory()->trainer()->create();
        foreach (User::factory()->count(30)->create() as $i => $trainee) {
            Subscription::create([
                'trainee_id' => $trainee->id, 'coach_id' => $coach->id, 'package_title' => 'Plan', 'price' => 40,
                'duration_days' => 30, 'status' => ['active', 'payment_review', 'awaiting_payment'][$i % 3],
                'starts_at' => now(), 'ends_at' => now()->addDays(30), 'payment_reference' => 'REF' . $i,
            ]);
        }

        foreach (['review', 'running', 'awaiting', 'all'] as $tab) {
            $this->actingAs($this->admin)->get(route('admin.subscriptions.index', ['tab' => $tab]))->assertOk();
        }

        $this->actingAs($this->admin)->get(route('admin.subscriptions.index', ['tab' => 'all']))
            ->assertSee(__('Page :page of :pages', ['page' => 1, 'pages' => 2]))
            ->assertSee(route('admin.subscriptions.index', ['tab' => 'all', 'page' => 2]));
        $this->actingAs($this->admin)->get(route('admin.subscriptions.index', ['tab' => 'all', 'page' => 2]))
            ->assertOk()->assertSee(__('Page :page of :pages', ['page' => 2, 'pages' => 2]));
        $this->actingAs($this->admin)->get(route('admin.subscriptions.index', ['tab' => 'all', 'q' => 'REF1']))->assertOk();
    }

    public function test_contact_messages_page(): void
    {
        foreach (range(1, 25) as $i) {
            ContactMessage::create(['name' => "Visitor $i", 'email' => "v$i@example.com", 'message' => 'Hello']);
        }

        $this->actingAs($this->admin)->get(route('admin.messages.index'))
            ->assertOk()->assertSee(__('Page :page of :pages', ['page' => 1, 'pages' => 2]));
        $this->actingAs($this->admin)->get(route('admin.messages.index', ['page' => 2]))->assertOk();
    }

    public function test_users_page_counts_everyone_and_links_the_next_page(): void
    {
        User::factory()->count(24)->create();

        $this->actingAs($this->admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(__('Total: :count users', ['count' => 25]))
            ->assertSee(route('admin.users.index', ['page' => 2]), false);
        $this->actingAs($this->admin)->get(route('admin.users.index', ['role' => 'trainee', 'page' => 2]))->assertOk();
    }
}
