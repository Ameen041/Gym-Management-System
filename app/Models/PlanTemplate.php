<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanTemplate extends Model
{
    protected $fillable = [
        'type',        // workout | nutrition
        'title',
        'description',
        'plan_details',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}