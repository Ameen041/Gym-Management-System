<?php

namespace App\Http\Controllers;

use App\Models\ChatBlockedAttempt;
use App\Models\ChatMessage;
use App\Models\ChatReport;
use App\Models\Conversation;
use App\Models\Subscription;
use App\Models\User;
use App\Support\ContactFilter;
use App\Support\Notify;
use App\Support\WebPush;
use Illuminate\Http\Request;

/**
 * Chat between a coach and their client, instead of WhatsApp: numbers stay
 * private and contact and payment stay on Himma. Messages that hold a phone
 * or account number, a link or another app are not sent (ContactFilter).
 */
class ChatController extends Controller
{
    public function index(Request $request)
    {
        $me = $request->user();

        $conversations = Conversation::for($me)->with(['coach', 'trainee'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_id', '!=', $me->id)->whereNull('read_at')])
            ->orderByRaw('case when last_message_at is null then 1 else 0 end')
            ->orderByDesc('last_message_at')->orderByDesc('id')
            ->get();

        // a client with one coach goes straight to the chat
        if ($me->role === 'trainee' && $conversations->count() === 1) {
            return redirect()->route('chat.show', $conversations->first());
        }

        $lastMessages = ChatMessage::whereIn('id', ChatMessage::selectRaw('max(id)')
            ->whereIn('conversation_id', $conversations->pluck('id'))->groupBy('conversation_id'))
            ->get()->keyBy('conversation_id');

        // open while a paid subscription runs, and a week after
        $lastEnds = Subscription::confirmed()
            ->where(fn ($q) => $q->where('coach_id', $me->id)->orWhere('trainee_id', $me->id))
            ->selectRaw('coach_id, trainee_id, max(ends_at) as last_end')->groupBy('coach_id', 'trainee_id')->get()
            ->mapWithKeys(fn ($row) => [$row->coach_id . '-' . $row->trainee_id => $row->last_end]);

        return view('chat.index', [
            'me'            => $me,
            'conversations' => $conversations,
            'lastMessages'  => $lastMessages,
            'open'          => $conversations->mapWithKeys(function (Conversation $c) use ($lastEnds) {
                $end = $lastEnds[$c->coach_id . '-' . $c->trainee_id] ?? null;

                return [$c->id => $end && \Carbon\Carbon::parse($end)->addDays(Conversation::OPEN_DAYS_AFTER_END)->isFuture()];
            }),
        ]);
    }

    /** Chat with a coach or client by their account (from the dashboard, a plan, the client page). */
    public function with(Request $request, User $user)
    {
        $me = $request->user();
        [$coachId, $traineeId] = $me->role === 'trainer' ? [$me->id, $user->id] : [$user->id, $me->id];

        $conversation = Conversation::where('coach_id', $coachId)->where('trainee_id', $traineeId)->first();
        if (!$conversation) {
            $paid = Subscription::where('coach_id', $coachId)->where('trainee_id', $traineeId)->confirmed()->exists();
            abort_unless($paid && $me->role !== $user->role, 404);
            $conversation = Conversation::between($coachId, $traineeId);
        }

        return redirect()->route('chat.show', [$conversation] + $request->only('draft'));
    }

    public function show(Request $request, Conversation $conversation)
    {
        $me = $request->user();
        abort_unless($conversation->hasMember($me), 404);
        $conversation->load(['coach', 'trainee']);

        $conversation->markReadFor($me);
        $me->unreadNotifications()->where('data', 'like', '%"tag":"chat-' . $conversation->id . '"%')->update(['read_at' => now()]);

        $before = $request->integer('before');
        $messages = $conversation->messages()
            ->when($before, fn ($q) => $q->where('id', '<', $before))
            ->latest('id')->limit(Conversation::PAGE_SIZE + 1)->get();
        $hasOlder = $messages->count() > Conversation::PAGE_SIZE;
        $messages = $messages->take(Conversation::PAGE_SIZE)->reverse()->values();

        $openUntil = $conversation->openUntil();
        $draft = $request->query('draft');

        return view('chat.show', [
            'me'           => $me,
            'conversation' => $conversation,
            'other'        => $conversation->otherMember($me),
            'messages'     => $messages,
            'hasOlder'     => $hasOlder,
            'open'         => (bool) $openUntil?->isFuture(),
            'openUntil'    => $openUntil,
            'subscription' => $conversation->currentSubscription(),
            'draft'        => is_string($draft) ? mb_substr($draft, 0, ChatMessage::MAX_LENGTH) : '',
            'seenId'       => $this->seenId($conversation, $me),
            'reported'     => $conversation->reports()->open()->where('reporter_id', $me->id)->exists(),
        ]);
    }

    public function store(Request $request, Conversation $conversation)
    {
        $me = $request->user();
        abort_unless($conversation->hasMember($me), 404);

        if (!$conversation->isOpen()) {
            return $this->refuse($request, __('This chat is closed: the subscription ended. Renew to keep talking.'));
        }

        $data = $request->validate(['body' => ['required', 'string', 'max:' . ChatMessage::MAX_LENGTH]]);
        $body = trim(str_replace("\r\n", "\n", $data['body']));

        if ($reason = ContactFilter::check($body)) {
            $this->keepBlocked($conversation, $me, $body, $reason);

            return $this->refuse($request, self::blockedText($reason));
        }

        $message = $conversation->messages()->create(['sender_id' => $me->id, 'body' => $body]);
        $conversation->update(['last_message_at' => $message->created_at]);

        // the bell shows one line per chat; the phone gets every message
        $other = $conversation->otherMember($me);
        $url = Notify::link('chat.show', $conversation);
        Notify::user($other, 'New message from :name.', ['name' => $me->name], $url, 'fa-comments', 'chat-' . $conversation->id, push: false);
        WebPush::send($other, $me->name, $body, $url, 'chat-' . $conversation->id);

        // the page then fetches new messages, so they stay in order
        if ($request->expectsJson()) {
            return response()->json(['id' => $message->id]);
        }

        return redirect(route('chat.show', $conversation) . '#m' . $message->id);
    }

    /** New messages since the last one on the page (the page asks every few seconds). */
    public function poll(Request $request, Conversation $conversation)
    {
        $me = $request->user();
        abort_unless($conversation->hasMember($me), 404);

        $after = $request->integer('after');
        $messages = $conversation->messages()->where('id', '>', $after)->orderBy('id')->limit(100)->get();
        if ($messages->where('sender_id', '!=', $me->id)->isNotEmpty()) {
            $conversation->markReadFor($me);
            $me->unreadNotifications()->where('data', 'like', '%"tag":"chat-' . $conversation->id . '"%')->update(['read_at' => now()]);
        }

        return response()->json([
            'html'    => $messages->isEmpty() ? '' : view('chat._messages', [
                'messages' => $messages, 'me' => $me, 'previousDay' => $this->dayOf($after, $conversation),
            ])->render(),
            'last_id' => $messages->last()?->id ?? $after,
            'seen_id' => $this->seenId($conversation, $me),
        ]);
    }

    /** Someone in the chat asks the admin to look at it (it's the only way the admin can read it). */
    public function report(Request $request, Conversation $conversation)
    {
        $me = $request->user();
        abort_unless($conversation->hasMember($me), 404);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        ChatReport::updateOrCreate(
            ['conversation_id' => $conversation->id, 'reporter_id' => $me->id, 'resolved_at' => null],
            ['reason' => $data['reason'] ?? null]
        );

        Notify::admins(':name reported a chat and asks you to look at it.', ['name' => $me->name],
            Notify::link('admin.chats.show', $conversation), 'fa-flag', 'chat-report-' . $conversation->id);

        return redirect()->route('chat.show', $conversation)
            ->with('success', __('Thanks. The Himma team will read this chat and contact you.'));
    }

    public static function blockedText(string $reason): string
    {
        return match ($reason) {
            'number'  => __('Long numbers can\'t be sent in the chat, to keep phone and account numbers private. For weights or reps, separate them with commas: 60, 70, 80.'),
            'link'    => __('Links can\'t be sent in the chat.'),
            'contact' => __('Emails and usernames can\'t be sent in the chat.'),
            default   => __('Talking about other apps or paying outside Himma isn\'t allowed in the chat. Contact and payment stay on Himma, to protect you both.'),
        };
    }

    private function keepBlocked(Conversation $conversation, User $me, string $body, string $reason): void
    {
        ChatBlockedAttempt::create([
            'conversation_id' => $conversation->id, 'sender_id' => $me->id, 'body' => $body, 'reason' => $reason,
        ]);

        $recent = ChatBlockedAttempt::where('sender_id', $me->id)
            ->where('created_at', '>=', now()->subDays(ChatBlockedAttempt::ALERT_DAYS))->count();
        if ($recent % ChatBlockedAttempt::ALERT_AFTER === 0) {
            Notify::admins(':name tried :count times to share contact details in the chat.', ['name' => $me->name, 'count' => $recent],
                Notify::link('admin.chats.index'), 'fa-user-shield', 'chat-blocked-' . $me->id);
        }
    }

    private function refuse(Request $request, string $error)
    {
        return $request->expectsJson()
            ? response()->json(['error' => $error], 422)
            : back()->withInput()->with('error', $error);
    }

    /** The newest of my messages the other person has read. */
    private function seenId(Conversation $conversation, User $me): int
    {
        return (int) $conversation->messages()->where('sender_id', $me->id)->whereNotNull('read_at')->max('id');
    }

    /** Day (in local time) of the last message up to $id, so the next ones know when a new day starts. */
    private function dayOf(int $id, Conversation $conversation): ?string
    {
        $message = $id > 0 ? $conversation->messages()->where('id', '<=', $id)->latest('id')->first() : null;

        return $message?->created_at->timezone(config('app.display_timezone'))->toDateString();
    }
}
