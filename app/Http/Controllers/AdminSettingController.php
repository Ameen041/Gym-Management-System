<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AdminSettingController extends Controller
{
    public function edit()
    {
        $settings = collect(Setting::DEFAULTS)->map(fn ($default, $key) => Setting::get($key, ''));

        return view('admin.settings.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'commission_rate'      => ['required', 'numeric', 'min:0', 'max:50'],
            'shamcash_name'        => ['nullable', 'string', 'max:255'],
            'shamcash_account'     => ['nullable', 'string', 'max:255'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
            'support_whatsapp'     => ['nullable', 'string', 'max:30'],
            'shamcash_qr_file'     => ['nullable', 'image', 'max:2048'],
            'remove_qr'            => ['nullable', 'boolean'],
        ]);

        foreach (['commission_rate', 'shamcash_name', 'shamcash_account', 'payment_instructions', 'support_whatsapp'] as $key) {
            Setting::put($key, $data[$key] ?? '');
        }

        $currentQr = Setting::get('shamcash_qr', '');
        if ($request->boolean('remove_qr') || $request->hasFile('shamcash_qr_file')) {
            if ($currentQr && str_starts_with($currentQr, 'uploads/settings/')) {
                File::delete(public_path($currentQr));
            }
            Setting::put('shamcash_qr', '');
        }
        if ($request->hasFile('shamcash_qr_file')) {
            $file = $request->file('shamcash_qr_file');
            $name = Str::uuid() . '.' . strtolower($file->getClientOriginalExtension());
            $file->move(public_path('uploads/settings'), $name);
            Setting::put('shamcash_qr', 'uploads/settings/' . $name);
        }

        return back()->with('status', __('Settings saved.'));
    }
}
