<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoachPayout extends Model
{
    protected $fillable = ['coach_id', 'amount', 'method', 'account', 'reference', 'note', 'created_by'];

    protected $casts = ['amount' => 'float'];

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }
}
