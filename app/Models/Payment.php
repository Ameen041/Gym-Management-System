<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'method',
        'paid_at',
        'period_start',
        'period_end',
        'reference',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}