<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\PlanPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanPriceFactory extends Factory
{
    protected $model = PlanPrice::class;

    public function definition(): array
    {
        return [
            'plan_id'       => Plan::factory(),
            'billing_cycle' => $this->faker->randomElement(['monthly', 'yearly']),
            'currency'      => $this->faker->randomElement(['USD', 'AED', 'EGP']),
            'price'         => $this->faker->randomFloat(2, 9, 999),
            'is_active'     => true,
        ];
    }
}
