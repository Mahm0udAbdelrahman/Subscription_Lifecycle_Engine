<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);
        return [
            'name'              => ucfirst($name),
            'description'       => $this->faker->sentence(),
            'trial_period_days' => 14,
            'is_active'         => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
