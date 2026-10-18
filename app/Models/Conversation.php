<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * The chat between a coach and one client. One per pair, kept across renewals.
 * People can write while a paid subscription runs and for a week after it ends.
 */
class Conversation extends Model
{
    public const OPEN_DAYS_AFTER_END = 7;
    public const PAGE_SIZE = 60;

    protected $fillable = ['coach_id', 'trainee_id', 'last_message_at'];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function trainee()
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function blockedAttempts()
    {
        return $this->hasMany(ChatBlockedAttempt::class);
    }

    public function reports()
    {
        return $this->hasMany(ChatReport::class);
    }

    public static function between(int $coachId, int $traineeId): self
    {
        return self::firstOrCreate(['coach_id' => $coachId, 'trainee_id' => $traineeId]);
    }

    /** Conversations the user is part of. */
    public function scopeFor($query, User $user)
    {
        return $query->where(fn ($q) => $q->where('coach_id', $user->id)->orWhere('trainee_id', $user->id));
    }

    public function hasMember(User $user): bool
    {
        return in_array((int) $user->id, [(int) $this->coach_id, (int) $this->trainee_id], true);
    }

    /** The other person in the chat. */
    public function otherMember(User $user): User
    {
        return (int) $user->id === (int) $this->coach_id ? $this->trainee : $this->coach;
    }

    private function paidSubscriptions()
    {
        return Subscription::where('coach_id', $this->coach_id)->where('trainee_id', $this->trainee_id)->confirmed();
    }

    /** Until when people can write: a week after the last paid subscription ends. */
    public function openUntil(): ?Carbon
    {
        $end = $this->paidSubscriptions()->max('ends_at');

        return $end ? Carbon::parse($end)->addDays(self::OPEN_DAYS_AFTER_END) : null;
    }

    public function isOpen(): bool
    {
        return (bool) $this->openUntil()?->isFuture();
    }

    /** The subscription the chat is about: the one running now, else the latest. */
    public function currentSubscription(): ?Subscription
    {
        return $this->paidSubscriptions()->where('starts_at', '<=', now())->latest('starts_at')->first()
            ?? $this->paidSubscriptions()->latest('starts_at')->first();
    }

    public function unreadFor(User $user): int
    {
        return $this->messages()->where('sender_id', '!=', $user->id)->whereNull('read_at')->count();
    }

    public function markReadFor(User $user): int
    {
        return $this->messages()->where('sender_id', '!=', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
    }

    /** Unread messages in all the user's conversations (the menu badge). */
    public static function unreadCountFor(User $user): int
    {
        return ChatMessage::whereNull('read_at')->where('sender_id', '!=', $user->id)
            ->whereIn('conversation_id', self::for($user)->select('id'))->count();
    }

    /** The admin may read a chat only after someone in it reported it. */
    public function isReported(): bool
    {
        return $this->reports()->exists();
    }
}
