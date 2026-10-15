<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    public const STATUS_LABELS = [
        'awaiting_payment' => 'Awaiting payment',
        'payment_review'   => 'Payment under review',
        'active'           => 'Active',
        'expired'          => 'Ended',
        'rejected'         => 'Payment rejected',
        'cancelled'        => 'Cancelled',
    ];

    // How many days the coach has to deliver the plan after activation
    public const PLAN_DUE_DAYS = 3;

    // Body photos and payment receipts are private: kept outside public/ and
    // only shown through SubscriptionFileController to the people involved.
    public const FILES_DISK = 'local';

    protected $fillable = [
        'trainee_id', 'coach_id', 'package_id', 'package_title', 'includes_workout', 'includes_nutrition',
        'duration_days', 'price', 'commission_rate', 'commission_amount', 'coach_amount', 'status',
        'intake', 'body_photos', 'payment_method', 'payment_reference', 'payment_proof', 'admin_note',
        'payment_submitted_at', 'confirmed_at', 'confirmed_by', 'starts_at', 'ends_at',
    ];

    protected $casts = [
        'intake'               => 'array',
        'body_photos'          => 'array',
        'includes_workout'     => 'boolean',
        'includes_nutrition'   => 'boolean',
        'price'                => 'float',
        'commission_amount'    => 'float',
        'coach_amount'         => 'float',
        'payment_submitted_at' => 'datetime',
        'confirmed_at'         => 'datetime',
        'starts_at'            => 'datetime',
        'ends_at'              => 'datetime',
    ];

    public function trainee()
    {
        return $this->belongsTo(User::class, 'trainee_id');
    }

    public function coach()
    {
        return $this->belongsTo(User::class, 'coach_id');
    }

    public function package()
    {
        return $this->belongsTo(CoachPackage::class, 'package_id');
    }

    public function workoutPlan()
    {
        return $this->hasOne(WorkoutPlan::class);
    }

    public function nutritionPlan()
    {
        return $this->hasOne(NutritionPlan::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function checkIns()
    {
        return $this->hasMany(CheckIn::class);
    }

    /**
     * When the next weekly check-in opens and is due: a week after the last
     * check-in with this coach (renewals included), or after the start.
     *
     * @param  CheckIn|null|false  $last  the last check-in if already loaded (null = none), false = look it up
     * @return array{opens: \Carbon\Carbon, due: \Carbon\Carbon, last: ?CheckIn}
     */
    public function checkInSchedule(CheckIn|null|false $last = false): array
    {
        if ($last === false) {
            $last = CheckIn::where('trainee_id', $this->trainee_id)->where('coach_id', $this->coach_id)
                ->latest()->latest('id')->first();
        }
        $due = ($last?->created_at ?? $this->starts_at ?? now())->copy()->startOfDay()->addDays(CheckIn::EVERY_DAYS);

        return ['last' => $last, 'opens' => $due->copy()->subDays(CheckIn::OPENS_EARLY_DAYS), 'due' => $due];
    }

    /** Weight and date at the start, from the answers of the first subscription with this coach. */
    public function startingPoint(): ?array
    {
        $first = self::where('trainee_id', $this->trainee_id)->where('coach_id', $this->coach_id)
            ->confirmed()->oldest('starts_at')->first() ?? $this;
        $weight = $first->intake['weight'] ?? null;

        return is_numeric($weight) && $first->starts_at
            ? ['date' => $first->starts_at, 'weight' => (float) $weight]
            : null;
    }

    /** Link to the payment receipt (admin and the trainee only). */
    public function proofUrl(): ?string
    {
        return $this->payment_proof ? route('subscriptions.file', [$this, 'proof']) : null;
    }

    /** Links to the trainee's body photos (trainee, their coach and the admin). */
    public function photoUrls(): array
    {
        return collect($this->body_photos ?? [])->keys()
            ->map(fn ($i) => route('subscriptions.file', [$this, 'photo', $i]))->all();
    }

    public function fileVisibleTo(User $user, string $kind): bool
    {
        return $user->role === 'admin'
            || (int) $user->id === (int) $this->trainee_id
            || ($kind === 'photo' && (int) $user->id === (int) $this->coach_id);
    }

    /** Paid and confirmed by the admin (counts for earnings). */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'active');
    }

    /** Confirmed, started and not ended yet. */
    public function scopeRunning($query)
    {
        return $query->where('status', 'active')->where('starts_at', '<=', now())->where('ends_at', '>', now());
    }

    /** Confirmed and not ended: running now, or a renewal waiting for the current one to end. */
    public function scopeNotEnded($query)
    {
        return $query->where('status', 'active')->where('ends_at', '>', now());
    }

    public function isRunning(): bool
    {
        return $this->status === 'active' && $this->ends_at && $this->ends_at->isFuture()
            && (!$this->starts_at || !$this->starts_at->isFuture());
    }

    /** A paid renewal that starts when the current subscription ends. */
    public function isUpcoming(): bool
    {
        return $this->status === 'active' && $this->starts_at && $this->starts_at->isFuture();
    }

    /** The next subscription with the same coach, if the trainee renewed (paid or still paying). */
    public function renewal(): ?self
    {
        return self::where('trainee_id', $this->trainee_id)->where('coach_id', $this->coach_id)
            ->whereKeyNot($this->id)->where('created_at', '>=', $this->created_at)
            ->whereIn('status', ['awaiting_payment', 'payment_review', 'rejected', 'active'])
            ->where(fn ($q) => $q->where('status', '!=', 'active')->orWhere('starts_at', '>=', $this->starts_at))
            ->oldest()->first();
    }

    /** The most recent earlier subscription with this coach (for plans to carry over). */
    public function previousWithCoach(): ?self
    {
        return self::where('trainee_id', $this->trainee_id)->where('coach_id', $this->coach_id)
            ->whereKeyNot($this->id)->confirmed()
            ->where('starts_at', '<', $this->starts_at ?? now())
            ->latest('starts_at')->first();
    }

    /** Whole days left until the end (0 on the last day). */
    public function daysLeft(): ?int
    {
        return $this->ends_at ? max(0, (int) today()->diffInDays($this->ends_at->copy()->startOfDay(), false)) : null;
    }

    /** Status shown to people: an active subscription past its end date is "expired". */
    public function getDisplayStatusAttribute(): string
    {
        if ($this->status === 'active' && $this->ends_at && $this->ends_at->isPast()) {
            return 'expired';
        }

        return $this->status;
    }

    public function getStatusLabelAttribute(): string
    {
        return __(self::STATUS_LABELS[$this->display_status] ?? $this->display_status);
    }

    public function getPlanDueAtAttribute()
    {
        return $this->starts_at?->copy()->addDays(self::PLAN_DUE_DAYS);
    }

    /** Plans the coach still has to send for this subscription. */
    public function missingPlans(): array
    {
        $missing = [];
        if ($this->includes_workout && !$this->workoutPlan)     $missing[] = 'workout';
        if ($this->includes_nutrition && !$this->nutritionPlan) $missing[] = 'nutrition';

        return $missing;
    }
}
