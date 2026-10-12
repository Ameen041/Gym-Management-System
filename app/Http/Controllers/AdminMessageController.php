<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;

class AdminMessageController extends Controller
{
    public function index()
    {
        $messages = ContactMessage::latest()->paginate(20);

        return view('admin.messages.index', compact('messages'));
    }

    public function markRead(ContactMessage $message)
    {
        $message->update(['is_read' => true]);

        return back();
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();

        return back()->with('status', __('Message deleted.'));
    }
}
