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
        'subscription_id',
        'targets',
        'created_at',
        'updated_at',
    ];

    // daily targets: calories, protein, carbs, fat
    protected $casts = [
        'targets' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
