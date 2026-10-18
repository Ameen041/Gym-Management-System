<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'age',
        'height',
        'weight',
        'profile_photo',
        'phone',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /* ---------- Coach marketplace ---------- */

    public function coachProfile()
    {
        return $this->hasOne(CoachProfile::class);
    }

    public function packages()
    {
        return $this->hasMany(CoachPackage::class, 'coach_id');
    }

    public function coachSubscriptions()
    {
        return $this->hasMany(Subscription::class, 'coach_id');
    }

    public function traineeSubscriptions()
    {
        return $this->hasMany(Subscription::class, 'trainee_id');
    }

    public function payouts()
    {
        return $this->hasMany(CoachPayout::class, 'coach_id');
    }

    public function pushSubscriptions()
    {
        return $this->hasMany(PushSubscription::class);
    }

    /** Coach profile, created empty on first use. */
    public function ensureCoachProfile(): CoachProfile
    {
        if (!$this->coachProfile) {
            // keep it on the model, so a second call doesn't try to create it again
            $this->setRelation('coachProfile', $this->coachProfile()->create(['status' => 'pending']));
        }

        return $this->coachProfile;
    }

    public function getPhotoUrlAttribute(): string
    {
        return $this->profile_photo ? asset('storage/' . $this->profile_photo) : asset('images/avatar.svg');
    }

    /** wa.me link (Syrian numbers like 09xxxxxxxx become 9639xxxxxxxx). */
    public function getWhatsappUrlAttribute(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '09')) {
            $digits = '963' . substr($digits, 1);
        }

        return 'https://wa.me/' . $digits;
    }

    /** Earnings of a coach: confirmed subscriptions minus payouts already sent. */
    /**
     * Money of a coach (after the platform commission).
     *  - earned:    all confirmed subscriptions
     *  - paid:      payouts already sent
     *  - balance:   earned - paid (everything still owed)
     *  - available: part of the balance that can be paid now. A subscription's money
     *               becomes available once its plans were sent AND the hold period
     *               (Setting payout_hold_days) after the payment confirmation passed,
     *               so a trainee can still be refunded if the coach never delivers.
     *  - pending:   balance - available
     */
    public function coachEarnings(): array
    {
        $holdDays = max(0, (int) Setting::get('payout_hold_days'));
        $subscriptions = $this->coachSubscriptions()->confirmed()->with(['workoutPlan', 'nutritionPlan'])->get();

        $earned = (float) $subscriptions->sum('coach_amount');
        $released = (float) $subscriptions
            ->filter(fn (Subscription $s) => !$s->missingPlans()
                && ($s->confirmed_at ?? $s->starts_at)?->copy()->addDays($holdDays)->isPast())
            ->sum('coach_amount');
        $paid = (float) $this->payouts()->sum('amount');

        $balance = round($earned - $paid, 2);
        $available = round(min($balance, max(0, $released - $paid)), 2);

        return [
            'earned'    => $earned,
            'paid'      => $paid,
            'balance'   => $balance,
            'available' => $available,
            'pending'   => round(max(0, $balance - $available), 2),
            'hold_days' => $holdDays,
        ];
    }
}
