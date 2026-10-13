<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A food of the nutrition library. Nutrition values are per 100 g.
 */
class Food extends Model
{
    protected $table = 'foods';

    public const CATEGORIES = [
        'protein'     => 'Meat, poultry & fish',
        'eggs_dairy'  => 'Eggs & dairy',
        'grains'      => 'Grains, bread & starches',
        'legumes'     => 'Legumes',
        'dishes'      => 'Prepared dishes',
        'vegetables'  => 'Vegetables',
        'fruits'      => 'Fruits',
        'nuts'        => 'Nuts & seeds',
        'fats'        => 'Oils & fats',
        'sweets'      => 'Sweets & drinks',
        'supplements' => 'Supplements',
        'other'       => 'Other',
    ];

    protected $fillable = [
        'name', 'name_ar', 'category', 'calories', 'protein', 'carbs', 'fat', 'fiber',
        'serving_grams', 'serving_name', 'serving_name_ar', 'usda_ndb', 'is_active', 'created_by',
    ];

    protected $casts = [
        'calories'      => 'float',
        'protein'       => 'float',
        'carbs'         => 'float',
        'fat'           => 'float',
        'fiber'         => 'float',
        'serving_grams' => 'float',
        'is_active'     => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getDisplayNameAttribute(): string
    {
        return app()->isLocale('ar') && filled($this->name_ar) ? $this->name_ar : $this->name;
    }

    public function getDisplayServingAttribute(): ?string
    {
        if (!$this->serving_grams) {
            return null;
        }
        $label = app()->isLocale('ar') && filled($this->serving_name_ar) ? $this->serving_name_ar : $this->serving_name;

        return $label ?: null;
    }

    public function getCategoryLabelAttribute(): string
    {
        return __(self::CATEGORIES[$this->category] ?? ucfirst((string) $this->category));
    }

    public function getSourceUrlAttribute(): ?string
    {
        return $this->usda_ndb ? 'https://fdc.nal.usda.gov/food-search?query=' . $this->usda_ndb . '&type=SR%20Legacy' : null;
    }

    /** Calories and macros for a given weight in grams, rounded for display. */
    public function nutritionFor(float $grams): array
    {
        $factor = $grams / 100;

        return [
            'calories' => round($this->calories * $factor),
            'protein'  => round($this->protein * $factor, 1),
            'carbs'    => round($this->carbs * $factor, 1),
            'fat'      => round($this->fat * $factor, 1),
        ];
    }
}
