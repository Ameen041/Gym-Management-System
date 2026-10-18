<?php

namespace App\Support;

use App\Models\CheckIn;
use App\Models\Conversation;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Someone deletes their own account. Personal data, photos, health answers,
 * check-ins and chats are deleted; the account row stays without a name so
 * the payment records (amounts, dates, transaction numbers, receipts) and
 * the coaches' earnings still add up.
 */
class AccountDeletion
{
    /** @return string[] why the account can't be deleted yet (empty: it can) */
    public static function blockers(User $user): array
    {
        $blockers = [];

        if ($user->traineeSubscriptions()->where('status', 'payment_review')->exists()) {
            $blockers[] = __('Your payment is being reviewed. Wait until it is confirmed or rejected, then you can delete your account.');
        }

        if ($user->role === 'trainer') {
            $clients = $user->coachSubscriptions()->notEnded()->count();
            if ($clients) {
                $blockers[] = trans_choice('{1} You have an active client. You can delete your account after the subscription ends.|[2,*] You have :count active clients. You can delete your account after their subscriptions end.', $clients);
            }
            $balance = $user->coachEarnings()['balance'];
            if ($balance > 0) {
                $blockers[] = __('Himma still owes you :amount. Ask the admin to send it first.', ['amount' => Money::format($balance)]);
            }
        }

        return $blockers;
    }

    public static function run(User $user): void
    {
        $private = Storage::disk(Subscription::FILES_DISK);
        $public = Storage::disk('public');
        $files = [];   // deleted once the database changes are saved

        DB::transaction(function () use ($user, &$files) {
            // as a client: answers and photos go, the payment stays
            foreach ($user->traineeSubscriptions()->get() as $sub) {
                $files = array_merge($files, (array) $sub->body_photos);
                $changes = ['intake' => null, 'body_photos' => null];
                if (in_array($sub->status, ['awaiting_payment', 'rejected'], true)) {
                    $changes['status'] = 'cancelled';
                } elseif ($sub->status === 'active' && $sub->ends_at?->isFuture()) {
                    $changes['ends_at'] = now();   // ends now, without a refund
                    if ($sub->starts_at?->isFuture()) {
                        $changes['starts_at'] = now();
                    }
                }
                $sub->update($changes);
            }
            foreach (CheckIn::where('trainee_id', $user->id)->get() as $checkIn) {
                $files = array_merge($files, (array) $checkIn->photos);
                $checkIn->delete();
            }

            Conversation::for($user)->delete();   // messages, reports and stopped messages go with it

            // as a coach: off the coaches page, packages closed
            if ($profile = $user->coachProfile) {
                $files = array_merge($files, array_map(fn ($p) => ['public', $p], (array) $profile->certificates));
                if ($profile->shamcash_qr) {
                    $files[] = $profile->shamcash_qr;
                }
                $profile->update([
                    'bio' => null, 'specialties' => null, 'city' => null, 'certificates' => null, 'status' => 'rejected',
                    'rejection_reason' => 'Account deleted.', 'is_featured' => false,
                    'shamcash_name' => null, 'shamcash_account' => null, 'shamcash_qr' => null,
                ]);
                $user->packages()->update(['is_active' => false]);
            }

            if ($user->profile_photo) {
                $files[] = ['public', $user->profile_photo];
            }
            $user->pushSubscriptions()->delete();
            $user->notifications()->delete();

            $user->forceFill([
                'name'           => 'حساب محذوف',
                'email'          => 'deleted-' . $user->id . '@deleted.invalid',
                'password'       => Hash::make(Str::random(40)),
                'phone'          => null,
                'age'            => null,
                'height'         => null,
                'weight'         => null,
                'profile_photo'  => null,
                'locale'         => null,
                'is_active'      => false,
                'deleted_at'     => now(),
            ])->save();
        });

        foreach ($files as $file) {
            [$disk, $path] = is_array($file) ? [$public, $file[1]] : [$private, $file];
            if ($path) {
                $disk->delete($path);
            }
        }
    }
}
