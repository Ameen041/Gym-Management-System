<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NutritionPlan extends Model
{
    protected $fillable = [
        'user_id',
        'trainer_id',
        'title',
        'plan_details',
        'created_at',
        'updated_at',
        
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}