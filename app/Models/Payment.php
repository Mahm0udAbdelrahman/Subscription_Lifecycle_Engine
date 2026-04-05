<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\PaymentStatus;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'amount',
        'currency',
        'status',
        'transaction_id',
        'metadata',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'   => 'decimal:2',
            'currency' => Currency::class,
            'status'   => PaymentStatus::class,
            'metadata' => 'array',
            'paid_at'  => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
