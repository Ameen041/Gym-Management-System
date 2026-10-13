<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoachPackage extends Model
{
    protected $fillable = [
        'coach_id', 'title', 'description', 'includes_workout', 'includes_nutrition',
        'price', 'duration_days', 'is_active',
    ];

    protected $casts = [
        'includes_workout'   => 'boolean',
        'includes_nutrition' => 'boolean',
        'is_active'          => 'boolean',
        'price'              => 'float',
    ];

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function getIncludesLabelAttribute(): string
    {
        $parts = [];
        if ($this->includes_workout)   $parts[] = __('Workout plan');
        if ($this->includes_nutrition) $parts[] = __('Nutrition plan');
        $parts[] = __('WhatsApp follow-up');

        return implode(' + ', $parts);
    }
}
