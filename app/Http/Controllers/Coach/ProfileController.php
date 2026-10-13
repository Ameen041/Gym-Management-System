<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\CoachProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit()
    {
        $coach = auth()->user();

        return view('coach.profile', [
            'coach'   => $coach,
            'profile' => $coach->ensureCoachProfile(),
        ]);
    }

    public function update(Request $request)
    {
        $coach = auth()->user();
        $profile = $coach->ensureCoachProfile();

        $data = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'phone'              => ['required', 'string', 'max:30', 'regex:/^[0-9+\s-]{8,20}$/'],
            'photo'              => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'bio'                => ['required', 'string', 'min:30', 'max:2000'],
            'specialties'        => ['required', 'array', 'min:1'],
            'specialties.*'      => [Rule::in(array_keys(CoachProfile::SPECIALTIES))],
            'experience_years'   => ['nullable', 'integer', 'min:0', 'max:60'],
            'city'               => ['nullable', Rule::in(array_keys(CoachProfile::CITIES))],
            'gender'             => ['nullable', Rule::in(['male', 'female'])],
            'certificates'       => ['nullable', 'array', 'max:6'],
            'certificates.*'     => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_certificates'   => ['nullable', 'array'],
            'remove_certificates.*' => ['string'],
        ]);

        if ($request->hasFile('photo')) {
            if ($coach->profile_photo) {
                Storage::disk('public')->delete($coach->profile_photo);
            }
            $coach->profile_photo = $request->file('photo')->store('profiles', 'public');
        }
        $coach->fill(['name' => $data['name'], 'phone' => $data['phone']])->save();

        $certificates = array_values(array_diff((array) $profile->certificates, $data['remove_certificates'] ?? []));
        foreach (array_intersect((array) $profile->certificates, $data['remove_certificates'] ?? []) as $path) {
            Storage::disk('public')->delete($path);
        }
        foreach ($request->file('certificates', []) as $file) {
            $certificates[] = $file->store('certificates', 'public');
        }

        $profile->fill([
            'bio'              => $data['bio'],
            'specialties'      => array_values($data['specialties']),
            'experience_years' => $data['experience_years'] ?? null,
            'city'             => $data['city'] ?? null,
            'gender'           => $data['gender'] ?? null,
            'certificates'     => $certificates ?: null,
        ]);

        // A rejected profile goes back to review after the coach fixes it
        if ($profile->status === 'rejected') {
            $profile->status = 'pending';
            $profile->rejection_reason = null;
        }
        $profile->save();

        return redirect()->route('trainer.dashboard')->with('success', __('Profile updated successfully.'));
    }
}
