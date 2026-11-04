<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The builder form of a plan the coach is still working on (the same fields the form sends),
 * so leaving the page loses nothing. It is removed once the plan is sent.
 */
class PlanDraft extends Model
{
    /** The form fields kept, per plan type */
    public const FIELDS = [
        'workout'   => ['title', 'day', 'exercise_id', 'exercise', 'muscle', 'sets', 'reps'],
        'nutrition' => ['title', 'targets', 'meals'],
    ];

    protected $fillable = ['subscription_id', 'type', 'data'];

    protected $casts = ['data' => 'array'];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
