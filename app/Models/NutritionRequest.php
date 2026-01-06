<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NutritionRequest extends Model
{
    protected $fillable = [
        'user_id',
         'goal', 
         'notes',
          'status',
          'trainer_id',
          'body_photos',
    ];

    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function trainer()
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }
}
