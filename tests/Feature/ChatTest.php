<?php

namespace Tests\Feature;

use App\Models\ChatBlockedAttempt;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use RefreshDatabase;

    private User $coach;
    private User $trainee;
    private User $admin;
    private Subscription $sub;
    private Conversation $chat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->coach = User::factory()->trainer()->create(['phone' => '0944000001']);
        $this->trainee = User::factory()->create(['phone' => '0933123456']);
        $this->sub = Subscription::create([
            'trainee_id' => $this->trainee->id, 'coach_id' => $this->coach->id, 'package_title' => 'Full coaching',
            'includes_workout' => true, 'includes_nutrition' => true, 'duration_days' => 30, 'price' => 40,
            'status' => 'active', 'starts_at' => now()->subDays(5), 'ends_at' => now()->addDays(25),
        ]);
        $this->chat = Conversation::between($this->coach->id, $this->trainee->id);
    }

    private function say(User $from, string $body)
    {
        return $this->actingAs($from)->postJson(route('chat.store', $this->chat), ['body' => $body]);
    }

    public function test_coach_and_client_talk_and_see_when_it_was_read(): void
    {
        $this->say($this->trainee, 'Can I swap squats for leg press?')->assertOk();
        $this->say($this->trainee, 'My knee hurts a bit')->assertOk();

        $this->assertSame(2, ChatMessage::count());
        $this->assertSame(1, $this->coach->unreadNotifications()->count(), 'one bell line per chat, not per message');
        $this->assertSame(2, Conversation::unreadCountFor($this->coach));

        $this->actingAs($this->coach)->get(route('trainer.dashboard'))->assertSee(trans_choice('{1} 1 new message|[2,*] :count new messages', 2));
        $this->actingAs($this->coach)->get(route('chat.show', $this->chat))
            ->assertOk()->assertSee('Can I swap squats for leg press?');

        $this->assertSame(0, Conversation::unreadCountFor($this->coach));
        $this->assertSame(0, $this->coach->unreadNotifications()->count());

        $poll = $this->actingAs($this->trainee)->getJson(route('chat.poll', [$this->chat, 'after' => 0]))->assertOk();
        $this->assertSame(ChatMessage::max('id'), $poll['seen_id']);
        $this->assertStringContainsString('My knee hurts a bit', $poll['html']);
    }

    public function test_poll_returns_only_new_messages(): void
    {
        $this->say($this->coach, 'Welcome!');
        $first = ChatMessage::sole()->id;
        $this->say($this->coach, 'Send me your weight on Friday');

        $poll = $this->actingAs($this->trainee)->getJson(route('chat.poll', [$this->chat, 'after' => $first]))->assertOk();
        $this->assertStringNotContainsString('Welcome!', $poll['html']);
        $this->assertStringContainsString('Send me your weight on Friday', $poll['html']);
        $this->assertSame(ChatMessage::max('id'), $poll['last_id']);
    }

    public function test_nobody_else_can_read_or_write(): void
    {
        $other = User::factory()->create();
        $otherCoach = User::factory()->trainer()->create();

        $this->actingAs($other)->get(route('chat.show', $this->chat))->assertNotFound();
        $this->actingAs($otherCoach)->get(route('chat.show', $this->chat))->assertNotFound();
        $this->actingAs($other)->postJson(route('chat.store', $this->chat), ['body' => 'hi'])->assertNotFound();
        $this->actingAs($other)->getJson(route('chat.poll', $this->chat))->assertNotFound();
        $this->actingAs($other)->get(route('chat.with', $this->coach))->assertNotFound();
        $this->actingAs($this->admin)->get(route('chat.show', $this->chat))->assertRedirect(route('admin.dashboard'));
        $this->assertSame(0, ChatMessage::count());
    }

    public function test_numbers_links_and_other_apps_are_stopped_and_kept_for_the_admin(): void
    {
        $this->say($this->coach, 'Send me on whatsapp 0944 000 001')->assertStatus(422)->assertJsonStructure(['error']);
        $this->say($this->coach, 'ابعتلي ع الواتس')->assertStatus(422);
        $this->assertSame(0, $this->admin->notifications()->count());

        $this->say($this->coach, 't.me/coach')->assertStatus(422);

        $this->assertSame(0, ChatMessage::count());
        $this->assertSame(3, ChatBlockedAttempt::where('sender_id', $this->coach->id)->count());
        $this->assertSame(1, $this->admin->notifications()->count(), 'the admin hears about it on the third try');
        $this->assertSame(0, $this->trainee->notifications()->count());

        $this->actingAs($this->admin)->get(route('admin.chats.index'))
            ->assertOk()->assertSee('t.me/coach')->assertSee(__('Tried several times'));

        $this->say($this->coach, '4 sets of 12, 10, 8, 6 then rest')->assertOk();
    }

    public function test_chat_stays_open_a_week_after_the_end_then_closes(): void
    {
        $this->sub->update(['starts_at' => now()->subDays(36), 'ends_at' => now()->subDays(6)]);
        $this->say($this->trainee, 'Thanks coach, I will renew next month')->assertOk();

        $this->travel(2)->days();
        $this->say($this->trainee, 'Hello?')->assertStatus(422);
        $this->actingAs($this->trainee)->get(route('chat.show', $this->chat))
            ->assertOk()->assertSee(__('This chat is closed: the subscription ended. Renew to keep talking.'))
            ->assertSee('Thanks coach, I will renew next month');
        $this->assertSame(1, ChatMessage::count());
    }

    public function test_admin_reads_a_chat_only_after_it_is_reported(): void
    {
        $this->say($this->coach, 'Pay me directly next time');

        $this->actingAs($this->admin)->get(route('admin.chats.show', $this->chat))->assertForbidden();

        $this->actingAs($this->trainee)->post(route('chat.report', $this->chat), ['reason' => 'Asked me to pay outside'])
            ->assertRedirect(route('chat.show', $this->chat));
        $this->assertSame(1, $this->admin->notifications()->count());

        $this->actingAs($this->admin)->get(route('admin.chats.show', $this->chat))
            ->assertOk()->assertSee('Pay me directly next time')->assertSee('Asked me to pay outside');

        $this->actingAs($this->admin)->post(route('admin.chats.resolve', $this->chat))->assertRedirect(route('admin.chats.index'));
        $this->assertNotNull($this->chat->reports()->sole()->resolved_at);
        $this->assertEqualsCanonicalizing(['New message from :name.', 'The Himma team reviewed the chat you reported.'],
            $this->trainee->notifications()->get()->pluck('data.message')->all());
    }

    public function test_confirming_a_payment_opens_the_chat(): void
    {
        $newTrainee = User::factory()->create();
        $sub = Subscription::create([
            'trainee_id' => $newTrainee->id, 'coach_id' => $this->coach->id, 'package_title' => 'Plan', 'duration_days' => 30,
            'price' => 25, 'status' => 'payment_review',
        ]);

        $this->actingAs($this->admin)->post(route('admin.subscriptions.confirm', $sub));

        $this->assertTrue(Conversation::where('coach_id', $this->coach->id)->where('trainee_id', $newTrainee->id)->exists());
    }

    public function test_coach_and_client_no_longer_see_each_others_phone(): void
    {
        $this->actingAs($this->trainee)->get(route('trainee.dashboard'))
            ->assertOk()->assertDontSee('wa.me')->assertSee(route('chat.show', $this->chat));
        $this->actingAs($this->coach)->get(route('coach.clients.show', $this->sub))
            ->assertOk()->assertDontSee('wa.me')->assertDontSee('0933123456')->assertSee(route('chat.with', $this->trainee));
    }

    public function test_renew_invite_opens_the_chat_with_a_ready_message(): void
    {
        $this->sub->update(['ends_at' => now()->addDays(3)]);
        $page = $this->actingAs($this->coach)->get(route('coach.clients.show', $this->sub))->assertOk();
        $page->assertSee(route('chat.with', $this->trainee) . '?draft=', false);

        $draft = 'Hi there, renew soon';
        $this->actingAs($this->coach)->get(route('chat.with', [$this->trainee, 'draft' => $draft]))
            ->assertRedirect(route('chat.show', [$this->chat, 'draft' => $draft]));
        $this->actingAs($this->coach)->get(route('chat.show', [$this->chat, 'draft' => $draft]))->assertSee($draft);
    }

    public function test_profiles_packages_and_check_ins_cannot_carry_a_number(): void
    {
        $this->actingAs($this->coach)->post(route('coach.packages.store'), [
            'title' => 'Full coaching', 'description' => 'Call me on 0944 000 001', 'price' => 40, 'duration_days' => 30,
            'includes_workout' => 1, 'includes_nutrition' => 1,
        ])->assertSessionHasErrors('description');

        $this->actingAs($this->coach)->post(route('coach.profile.update'), [
            'name' => 'Coach', 'phone' => '0944000001', 'bio' => 'Find me on instagram and telegram for more tips and offers.',
        ])->assertSessionHasErrors('bio');
    }
}
