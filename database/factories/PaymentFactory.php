<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $subscription = Subscription::factory()->create();
        return [
            'subscription_id' => $subscription->id,
            'amount'          => $subscription->price,
            'currency'        => $subscription->currency,
            'status'          => 'succeeded',
            'transaction_id'  => 'txn_' . Str::random(10),
            'paid_at'         => now(),
        ];
    }
}
