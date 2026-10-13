<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = ['subscription_id', 'trainee_id', 'coach_id', 'rating', 'comment'];

    public function trainee()
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
}
