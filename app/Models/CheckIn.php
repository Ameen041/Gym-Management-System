<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckIn extends Model
{
    // One check-in a week: the form opens a day early, a reminder goes out on the due day
    public const EVERY_DAYS = 7;
    public const OPENS_EARLY_DAYS = 1;

    public const DIET_SCALE = [1 => 'Not at all', 2 => 'A little', 3 => 'Half the time', 4 => 'Mostly', 5 => 'Fully'];
    public const FEEL_SCALE = [1 => 'Very bad', 2 => 'Bad', 3 => 'OK', 4 => 'Good', 5 => 'Very good'];

    protected $fillable = [
        'subscription_id', 'trainee_id', 'coach_id', 'weight', 'waist', 'workouts_done',
        'diet_adherence', 'energy', 'sleep', 'note', 'photos', 'coach_reply', 'replied_at',
    ];

    protected $casts = [
        'weight'         => 'float',
        'waist'          => 'float',
        'workouts_done'  => 'integer',
        'diet_adherence' => 'integer',
        'energy'         => 'integer',
        'sleep'          => 'integer',
        'photos'         => 'array',
        'replied_at'     => 'datetime',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function trainee()
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    /** All check-ins between a trainee and a coach, across renewals, oldest first. */
    public function scopeBetween($query, int $traineeId, int $coachId)
    {
        return $query->where('trainee_id', $traineeId)->where('coach_id', $coachId)->oldest()->orderBy('id');
    }

    public function scopeUnanswered($query)
    {
        return $query->whereNull('replied_at');
    }

    public function visibleTo(User $user): bool
    {
        return $user->role === 'admin'
            || (int) $user->id === (int) $this->trainee_id
            || (int) $user->id === (int) $this->coach_id;
    }

    public function photoUrls(): array
    {
        return collect($this->photos ?? [])->keys()
            ->map(fn ($i) => route('check-ins.photo', [$this, $i]))->all();
    }
}
