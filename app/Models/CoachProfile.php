<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoachProfile extends Model
{
    public const SPECIALTIES = [
        'weight_loss'  => 'Weight loss',
        'muscle_gain'  => 'Muscle gain',
        'strength'     => 'Strength',
        'fitness'      => 'General fitness',
        'bodybuilding' => 'Bodybuilding competitions',
        'nutrition'    => 'Sports nutrition',
        'rehab'        => 'Injury rehabilitation',
        'women'        => 'Women training',
    ];

    // Syrian governorates
    public const CITIES = [
        'damascus'    => 'Damascus',
        'rif_dimashq' => 'Rif Dimashq',
        'aleppo'      => 'Aleppo',
        'homs'        => 'Homs',
        'hama'        => 'Hama',
        'latakia'     => 'Latakia',
        'tartus'      => 'Tartus',
        'idlib'       => 'Idlib',
        'daraa'       => 'Daraa',
        'suwayda'     => 'As-Suwayda',
        'quneitra'    => 'Quneitra',
        'deir_ezzor'  => 'Deir ez-Zor',
        'raqqa'       => 'Raqqa',
        'hasakah'     => 'Al-Hasakah',
    ];

    protected $fillable = [
        'user_id', 'bio', 'specialties', 'experience_years', 'city', 'gender',
        'certificates', 'status', 'rejection_reason', 'is_featured', 'rating_avg', 'rating_count',
        'shamcash_name', 'shamcash_account', 'shamcash_qr', 'payout_account_updated_at', 'invite_code', 'invite_visits',
    ];

    protected $casts = [
        'specialties'  => 'array',
        'certificates' => 'array',
        'is_featured'  => 'boolean',
        'payout_account_updated_at' => 'datetime',
        'rating_avg'   => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** The coach's invite link; the code is made the first time it is needed. */
    public function inviteUrl(): string
    {
        if (!$this->invite_code) {
            do {
                $code = strtolower(\Illuminate\Support\Str::random(8));
            } while (self::where('invite_code', $code)->exists());
            $this->update(['invite_code' => $code]);
        }

        return route('invite', $this->invite_code);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function getSpecialtyLabelsAttribute(): array
    {
        return array_values(array_map(
            fn ($key) => __(self::SPECIALTIES[$key] ?? $key),
            array_filter((array) $this->specialties, fn ($key) => isset(self::SPECIALTIES[$key]))
        ));
    }

    public function getCityLabelAttribute(): ?string
    {
        return $this->city ? __(self::CITIES[$this->city] ?? $this->city) : null;
    }

    /**
     * What is still missing before the profile can be reviewed / listed.
     */
    public function missingSteps(): array
    {
        $user = $this->user;
        $missing = [];

        if (!$user->profile_photo)                        $missing[] = __('Profile photo');
        if (blank($this->bio))                            $missing[] = __('Bio');
        if (empty($this->specialties))                    $missing[] = __('Specialties');
        if (blank($user->phone))                          $missing[] = __('Phone number');
        if (!$user->packages()->where('is_active', true)->exists()) $missing[] = __('At least one package');

        return $missing;
    }

    public function hasPayoutAccount(): bool
    {
        return filled($this->shamcash_account);
    }

    /** Account changed in the last week: the admin should double-check before sending money. */
    public function payoutAccountChangedRecently(): bool
    {
        return $this->payout_account_updated_at !== null && $this->payout_account_updated_at->gt(now()->subDays(7));
    }

    /** "Name · account" as kept on each payout. */
    public function payoutAccountLabel(): ?string
    {
        return $this->hasPayoutAccount() ? trim($this->shamcash_name . ' · ' . $this->shamcash_account, ' ·') : null;
    }

    /** Account number with only the last 4 characters visible, e.g. "•••• 4567". */
    public function maskedPayoutAccount(): ?string
    {
        return $this->hasPayoutAccount() ? '•••• ' . mb_substr(preg_replace('/\s+/u', '', $this->shamcash_account), -4) : null;
    }

    /** Tell the admins when a pending profile has everything needed for review. */
    public function notifyAdminsIfReady(): void
    {
        if ($this->status !== 'pending' || $this->missingSteps()) {
            return;
        }

        \App\Support\Notify::admins('Coach :name completed the profile and is waiting for your review.', [
            'name' => $this->user->name,
        ], \App\Support\Notify::link('admin.coaches.show', $this->user_id), 'fa-user-clock', 'coach-review-' . $this->user_id);
    }

    public function refreshRating(): void
    {
        $stats = Review::where('coach_id', $this->user_id)
            ->selectRaw('COUNT(*) as total, AVG(rating) as average')
            ->first();

        $this->update([
            'rating_count' => (int) $stats->total,
            'rating_avg'   => round((float) $stats->average, 2),
        ]);
    }
}
