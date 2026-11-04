<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoachPackage extends Model
{
    protected $fillable = [
        'coach_id', 'title', 'description', 'includes_workout', 'includes_nutrition',
        'price', 'price_locked', 'duration_days', 'is_active',
    ];

    protected $casts = [
        'includes_workout'   => 'boolean',
        'includes_nutrition' => 'boolean',
        'is_active'          => 'boolean',
        'price_locked'       => 'boolean',
        'price'              => 'float',
    ];

    /** Packages whose price is outside the range the admin set (locked prices are the admin's own choice). */
    public function scopeOutOfRange($query)
    {
        $min = (float) Setting::get('package_min_monthly');
        $max = (float) Setting::get('package_max_monthly');

        return $query->where('price_locked', false)
            ->where(fn ($q) => $q->whereRaw('price * 30 < ? * duration_days', [$min])->orWhereRaw('price * 30 > ? * duration_days', [$max]));
    }

    public function getPriceRangeAttribute(): array
    {
        return Setting::packagePriceRange($this->duration_days);
    }

    public function getInRangeAttribute(): bool
    {
        [$min, $max] = $this->price_range;

        return $this->price_locked || ($this->price >= $min && $this->price <= $max);
    }

    /** The price for 30 days, to compare packages of different lengths. */
    public function getMonthlyPriceAttribute(): float
    {
        return round($this->price * 30 / max(1, $this->duration_days), 2);
    }

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function getIncludesLabelAttribute(): string
    {
        $parts = [];
        if ($this->includes_workout)   $parts[] = __('Workout plan');
        if ($this->includes_nutrition) $parts[] = __('Nutrition plan');
        $parts[] = __('Chat follow-up');

        return implode(' + ', $parts);
    }
}
