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

    /** Confirmed and still running. */
    public function scopeRunning($query)
    {
        return $query->where('status', 'active')->where('ends_at', '>', now());
    }

    public function isRunning(): bool
    {
        return $this->status === 'active' && $this->ends_at && $this->ends_at->isFuture();
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
