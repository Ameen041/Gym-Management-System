<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use App\Support\Notify;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validateWithBag('contact', [
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['required', 'email', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:50'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ContactMessage::create($data);

        Notify::admins('New message from :name on the contact form.', ['name' => $data['name']],
            Notify::link('admin.messages.index'), 'fa-envelope');

        return redirect()->to(route('home') . '#contact')
            ->with('contact_success', __('Thank you! Your message has been sent. We will get back to you soon.'));
    }
}
