<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use App\Models\CoachProfile;
use App\Models\Subscription;
use App\Models\User;
use App\Rules\NoContactDetails;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'bio'                => ['required', 'string', 'min:30', 'max:2000', new NoContactDetails],
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
        $profile->notifyAdminsIfReady();

        return redirect()->route('trainer.dashboard')->with('success', __('Profile updated successfully.'));
    }

    /**
     * Where the admin sends the coach's earnings. Kept apart from the public
     * profile; changing an existing account needs the password, and the admins
     * are told, so a stolen login can't quietly redirect the money.
     */
    public function updatePayout(Request $request)
    {
        $coach = auth()->user();
        $profile = $coach->ensureCoachProfile();
        $changing = $profile->hasPayoutAccount();

        $data = $request->validateWithBag('payout', [
            'shamcash_name'    => ['required', 'string', 'max:100'],
            'shamcash_account' => ['required', 'string', 'max:100', 'regex:/^[\pL0-9\s+\-_.]{4,100}$/u'],
            'shamcash_qr'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_qr'        => ['nullable', 'boolean'],
            'current_password' => $changing ? ['required', 'string'] : ['nullable'],
        ]);

        if ($changing && !Hash::check($data['current_password'], $coach->password)) {
            return back()->withErrors(['current_password' => __('The password is not correct.')], 'payout')->withInput();
        }

        $qr = $profile->shamcash_qr;
        if (($request->boolean('remove_qr') || $request->hasFile('shamcash_qr')) && $qr) {
            Storage::disk(Subscription::FILES_DISK)->delete($qr);
            $qr = null;
        }
        if ($request->hasFile('shamcash_qr')) {
            $qr = $request->file('shamcash_qr')->store('payout_qr', Subscription::FILES_DISK);
        }

        $accountChanged = $profile->shamcash_account !== $data['shamcash_account'] || $profile->shamcash_name !== $data['shamcash_name'];
        $profile->update([
            'shamcash_name'    => $data['shamcash_name'],
            'shamcash_account' => $data['shamcash_account'],
            'shamcash_qr'      => $qr,
        ] + ($accountChanged || $qr !== $profile->getOriginal('shamcash_qr') ? ['payout_account_updated_at' => now()] : []));

        if ($changing && $accountChanged) {
            Notify::admins('Coach :name changed their Sham Cash account. Check with them before the next payout.', [
                'name' => $coach->name,
            ], Notify::link('admin.payouts.index'), 'fa-triangle-exclamation');
        }

        return redirect(route('coach.profile.edit') . '#payout')->with('success', __('Your Sham Cash account was saved.'));
    }

    /** The QR image of a coach's Sham Cash account: for the coach and the admin only. */
    public function payoutQr(User $coach)
    {
        abort_unless(auth()->user()->role === 'admin' || (int) auth()->id() === (int) $coach->id, 403);

        $path = $coach->coachProfile?->shamcash_qr;
        $disk = Storage::disk(Subscription::FILES_DISK);
        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, null, ['Cache-Control' => 'private, max-age=600']);
    }
}
