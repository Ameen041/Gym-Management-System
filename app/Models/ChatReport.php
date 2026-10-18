<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatReport extends Model
{
    protected $fillable = ['conversation_id', 'reporter_id', 'reason', 'resolved_at'];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function scopeOpen($query)
    {
        return $query->whereNull('resolved_at');
    }
}
