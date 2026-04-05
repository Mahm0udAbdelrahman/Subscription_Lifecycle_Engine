<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $planPrice = PlanPrice::factory()->create();
        
        return [
            'user_id'       => User::factory(),
            'plan_id'       => $planPrice->plan_id,
            'plan_price_id' => $planPrice->id,
            'status'        => SubscriptionStatus::Active,
            'starts_at'     => Carbon::now(),
            'ends_at'       => Carbon::now()->addMonth(),
            'price'         => $planPrice->price,
            'currency'      => $planPrice->currency,
            'billing_cycle' => $planPrice->billing_cycle,
        ];
    }

    public function trialing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'        => SubscriptionStatus::Trialing,
            'trial_ends_at' => Carbon::now()->addDays(14),
        ]);
    }

    public function expiredTrial(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'        => SubscriptionStatus::Trialing,
            'trial_ends_at' => Carbon::now()->subDay(),
            'starts_at'     => Carbon::now()->subDays(15),
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'               => SubscriptionStatus::PastDue,
            'grace_period_ends_at' => Carbon::now()->addDays(3),
        ]);
    }

    public function expiredGracePeriod(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'               => SubscriptionStatus::PastDue,
            'grace_period_ends_at' => Carbon::now()->subDay(),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status'      => SubscriptionStatus::Canceled,
            'canceled_at' => Carbon::now(),
        ]);
    }
}
