<?php

namespace App\Http\Controllers;

use App\Models\ChatBlockedAttempt;
use App\Models\ChatReport;
use App\Models\Conversation;
use App\Support\Notify;

/**
 * Chats are private: the admin sees the reported ones (to settle a problem)
 * and the messages that were stopped because they held contact details.
 */
class AdminChatController extends Controller
{
    public function index()
    {
        $since = now()->subDays(ChatBlockedAttempt::ALERT_DAYS);

        return view('admin.chats.index', [
            'openReports' => ChatReport::open()->with(['conversation.coach', 'conversation.trainee', 'reporter'])->latest()->get(),
            'oldReports'  => ChatReport::whereNotNull('resolved_at')->with(['conversation.coach', 'conversation.trainee', 'reporter'])
                ->latest('resolved_at')->limit(20)->get(),
            'attempts'    => ChatBlockedAttempt::with(['sender', 'conversation.coach', 'conversation.trainee'])->latest()->limit(50)->get(),
            'repeaters'   => ChatBlockedAttempt::where('created_at', '>=', $since)
                ->selectRaw('sender_id, count(*) as tries')->groupBy('sender_id')
                ->havingRaw('count(*) >= ?', [ChatBlockedAttempt::ALERT_AFTER])
                ->orderByDesc('tries')->with('sender')->get(),
        ]);
    }

    public function show(Conversation $conversation)
    {
        abort_unless($conversation->isReported(), 403, __('A chat can be read only after someone in it reported it.'));

        $conversation->load(['coach', 'trainee']);

        return view('admin.chats.show', [
            'conversation' => $conversation,
            'messages'     => $conversation->messages()->with('sender')->orderBy('id')->get(),
            'reports'      => $conversation->reports()->with('reporter')->latest()->get(),
            'attempts'     => $conversation->blockedAttempts()->with('sender')->latest()->get(),
        ]);
    }

    public function resolve(Conversation $conversation)
    {
        $reports = $conversation->reports()->open()->with('reporter')->get();
        $conversation->reports()->open()->update(['resolved_at' => now()]);

        foreach ($reports as $report) {
            Notify::user($report->reporter, 'The Himma team reviewed the chat you reported.', [], Notify::link('chat.show', $conversation), 'fa-flag');
        }

        return redirect()->route('admin.chats.index')->with('status', __('Report closed.'));
    }
}
