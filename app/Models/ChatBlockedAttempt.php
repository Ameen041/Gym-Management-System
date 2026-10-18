<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A chat message that was not sent because it held a number, a link or another app. Kept for the admin. */
class ChatBlockedAttempt extends Model
{
    // the admin is told when someone tries this many times within ALERT_DAYS days
    public const ALERT_AFTER = 3;
    public const ALERT_DAYS = 30;

    public const REASONS = [
        'number'  => 'Phone or account number',
        'link'    => 'Link',
        'contact' => 'Email or username',
        'app'     => 'Another app or paying outside',
    ];

    protected $fillable = ['conversation_id', 'sender_id', 'body', 'reason'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function getReasonLabelAttribute(): string
    {
        return __(self::REASONS[$this->reason] ?? $this->reason);
    }
}
