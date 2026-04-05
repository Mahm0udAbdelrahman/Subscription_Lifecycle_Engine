<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\Currency;
use App\Enums\SubscriptionStatus;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'plan_price_id',
        'status',
        'trial_ends_at',
        'starts_at',
        'ends_at',
        'canceled_at',
        'grace_period_ends_at',
        'price',
        'currency',
        'billing_cycle',
    ];

    protected function casts(): array
    {
        return [
            'status'                => SubscriptionStatus::class,
            'billing_cycle'         => BillingCycle::class,
            'currency'              => Currency::class,
            'price'                 => 'decimal:2',
            'trial_ends_at'         => 'datetime',
            'starts_at'             => 'datetime',
            'ends_at'               => 'datetime',
            'canceled_at'           => 'datetime',
            'grace_period_ends_at'  => 'datetime',
        ];
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function planPrice(): BelongsTo
    {
        return $this->belongsTo(PlanPrice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

  

    public function scopeActive($query)
    {
        return $query->where('status', SubscriptionStatus::Active);
    }

    public function scopeTrialing($query)
    {
        return $query->where('status', SubscriptionStatus::Trialing);
    }

    public function scopePastDue($query)
    {
        return $query->where('status', SubscriptionStatus::PastDue);
    }

    public function scopeAccessible($query)
    {
        return $query->whereIn('status', [
            SubscriptionStatus::Trialing,
            SubscriptionStatus::Active,
            SubscriptionStatus::PastDue,
        ]);
    }

    public function isAccessible(): bool
    {
        return $this->status->isAccessible();
    }

    public function isOnTrial(): bool
    {
        return $this->status === SubscriptionStatus::Trialing
            && $this->trial_ends_at?->isFuture();
    }

    public function isWithinGracePeriod(): bool
    {
        return $this->status === SubscriptionStatus::PastDue
            && $this->grace_period_ends_at?->isFuture();
    }

    public function hasExpiredTrial(): bool
    {
        return $this->status === SubscriptionStatus::Trialing
            && $this->trial_ends_at
            && $this->trial_ends_at->isPast();
    }

    public function hasExpiredGracePeriod(): bool
    {
        return $this->status === SubscriptionStatus::PastDue
            && $this->grace_period_ends_at
            && $this->grace_period_ends_at->isPast();
    }
}
