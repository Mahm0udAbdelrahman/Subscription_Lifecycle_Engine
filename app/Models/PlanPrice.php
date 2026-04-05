<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\Currency;
use Database\Factories\PlanPriceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanPrice extends Model
{
    /** @use HasFactory<PlanPriceFactory> */
    use HasFactory;

    protected $fillable = [
        'plan_id',
        'billing_cycle',
        'currency',
        'price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'billing_cycle' => BillingCycle::class,
            'currency'      => Currency::class,
            'price'         => 'decimal:2',
            'is_active'     => 'boolean',
        ];
    }



    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }


    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCycleAndCurrency($query, BillingCycle $cycle, Currency $currency)
    {
        return $query->where('billing_cycle', $cycle->value)
                     ->where('currency', $currency->value);
    }
}
