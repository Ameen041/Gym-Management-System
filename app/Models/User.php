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

    public function workoutPlan()
{
    return $this->hasOne(\App\Models\WorkoutPlan::class);
}

public function nutritionPlan()
{
    return $this->hasOne(\App\Models\NutritionPlan::class);
}

public function payments()
{
    return $this->hasMany(\App\Models\Payment::class);
}

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

    /** Coach profile, created empty on first use. */
    public function ensureCoachProfile(): CoachProfile
    {
        return $this->coachProfile ?: $this->coachProfile()->create(['status' => 'pending']);
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
    public function coachEarnings(): array
    {
        $earned = (float) $this->coachSubscriptions()->confirmed()->sum('coach_amount');
        $paid   = (float) $this->payouts()->sum('amount');

        return ['earned' => $earned, 'paid' => $paid, 'balance' => round($earned - $paid, 2)];
    }
}
