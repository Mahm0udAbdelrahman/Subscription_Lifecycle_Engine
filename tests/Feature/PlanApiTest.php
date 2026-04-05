<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanApiTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum');
        return $user;
    }

    private function createPlanWithPrices(): Plan
    {
        $plan = Plan::factory()->create([
            'name'              => 'Test Plan',
            'trial_period_days' => 7,
        ]);

        PlanPrice::factory()->create([
            'plan_id'       => $plan->id,
            'billing_cycle' => 'monthly',
            'currency'      => 'USD',
            'price'         => 19.99,
        ]);

        PlanPrice::factory()->create([
            'plan_id'       => $plan->id,
            'billing_cycle' => 'yearly',
            'currency'      => 'USD',
            'price'         => 199.99,
        ]);

        return $plan;
    }

    public function test_can_list_active_plans(): void
    {
        Plan::factory()->count(3)->create();
        Plan::factory()->inactive()->create();

        $response = $this->getJson('/api/plans');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_plan_with_prices(): void
    {
        $this->authenticatedUser();

        $response = $this->postJson('/api/plans', [
            'name'              => 'Premium Plan',
            'description'       => 'The best plan for teams.',
            'trial_period_days' => 14,
            'prices'            => [
                ['billing_cycle' => 'monthly', 'currency' => 'USD', 'price' => 29.99],
                ['billing_cycle' => 'yearly',  'currency' => 'USD', 'price' => 299.99],
                ['billing_cycle' => 'monthly', 'currency' => 'AED', 'price' => 109.99],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Premium Plan')
            ->assertJsonPath('data.trial_period_days', 14)
            ->assertJsonCount(3, 'data.prices');

        $this->assertDatabaseHas('plans', ['name' => 'Premium Plan']);
        $this->assertDatabaseCount('plan_prices', 3);
    }

    public function test_create_plan_validates_required_fields(): void
    {
        $this->authenticatedUser();

        $response = $this->postJson('/api/plans', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'prices']);
    }

    public function test_can_show_single_plan(): void
    {
        $plan = $this->createPlanWithPrices();

        $response = $this->getJson("/api/plans/{$plan->id}");

        $response->assertOk()
            ->assertJsonPath('data.name', 'Test Plan')
            ->assertJsonCount(2, 'data.prices');
    }

    public function test_can_update_plan(): void
    {
        $this->authenticatedUser();
        $plan = $this->createPlanWithPrices();

        $response = $this->putJson("/api/plans/{$plan->id}", [
            'name'              => 'Updated Plan',
            'trial_period_days' => 30,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Plan')
            ->assertJsonPath('data.trial_period_days', 30);
    }

    public function test_can_update_plan_prices(): void
    {
        $this->authenticatedUser();
        $plan = $this->createPlanWithPrices();

        $response = $this->putJson("/api/plans/{$plan->id}", [
            'prices' => [
                ['billing_cycle' => 'monthly', 'currency' => 'EGP', 'price' => 149.99],
            ],
        ]);

        $response->assertOk()
            ->assertJsonCount(1, 'data.prices');

        
        $this->assertDatabaseCount('plan_prices', 1);
    }

    public function test_can_deactivate_plan(): void
    {
        $this->authenticatedUser();
        $plan = $this->createPlanWithPrices();

        $response = $this->deleteJson("/api/plans/{$plan->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Plan deactivated successfully.');

        $this->assertDatabaseHas('plans', [
            'id'        => $plan->id,
            'is_active' => false,
        ]);
    }

    public function test_unauthenticated_cannot_create_plan(): void
    {
        $response = $this->postJson('/api/plans', [
            'name'   => 'Test',
            'prices' => [
                ['billing_cycle' => 'monthly', 'currency' => 'USD', 'price' => 9.99],
            ],
        ]);

        $response->assertUnauthorized();
    }
}
