<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Plan $plan;
    private PlanPrice $planPrice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');

        $this->plan = Plan::factory()->create([
            'name'              => 'Pro Plan',
            'trial_period_days' => 14,
        ]);

        $this->planPrice = PlanPrice::factory()->create([
            'plan_id'       => $this->plan->id,
            'billing_cycle' => 'monthly',
            'currency'      => 'USD',
            'price'         => 29.99,
        ]);
    }

    public function test_can_subscribe_to_plan_with_trial(): void
    {
        $response = $this->postJson('/api/subscriptions', [
            'plan_id'       => $this->plan->id,
            'billing_cycle' => 'monthly',
            'currency'      => 'USD',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'trialing')
            ->assertJsonPath('data.is_accessible', true)
            ->assertJsonPath('data.price', '29.99')
            ->assertJsonPath('data.currency', 'USD')
            ->assertJsonPath('data.billing_cycle', 'monthly');

        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $this->user->id,
            'plan_id' => $this->plan->id,
            'status'  => 'trialing',
        ]);
    }

    public function test_can_subscribe_to_plan_without_trial(): void
    {
        $this->plan->update(['trial_period_days' => 0]);

        $response = $this->postJson('/api/subscriptions', [
            'plan_id'       => $this->plan->id,
            'billing_cycle' => 'monthly',
            'currency'      => 'USD',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'active');
    }

    public function test_cannot_subscribe_twice(): void
    {
        
        $this->postJson('/api/subscriptions', [
            'plan_id'       => $this->plan->id,
            'billing_cycle' => 'monthly',
            'currency'      => 'USD',
        ]);

        
        $response = $this->postJson('/api/subscriptions', [
            'plan_id'       => $this->plan->id,
            'billing_cycle' => 'monthly',
            'currency'      => 'USD',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'User already has an active or trialing subscription.');
    }

    public function test_cannot_subscribe_to_inactive_plan(): void
    {
        $this->plan->update(['is_active' => false]);

        $response = $this->postJson('/api/subscriptions', [
            'plan_id'       => $this->plan->id,
            'billing_cycle' => 'monthly',
            'currency'      => 'USD',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'The selected plan is not active.');
    }

    public function test_returns_error_for_invalid_price_combination(): void
    {
        $response = $this->postJson('/api/subscriptions', [
            'plan_id'       => $this->plan->id,
            'billing_cycle' => 'yearly',  
            'currency'      => 'USD',
        ]);

        $response->assertStatus(422);
    }

    public function test_can_list_user_subscriptions(): void
    {
        Subscription::factory()->create([
            'user_id'       => $this->user->id,
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
        ]);

        $response = $this->getJson('/api/subscriptions');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_show_subscription_detail(): void
    {
        $subscription = Subscription::factory()->create([
            'user_id'       => $this->user->id,
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
        ]);

        $response = $this->getJson("/api/subscriptions/{$subscription->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $subscription->id);
    }

    public function test_cannot_view_other_users_subscription(): void
    {
        $otherUser = User::factory()->create();
        $subscription = Subscription::factory()->create([
            'user_id'       => $otherUser->id,
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
        ]);

        $response = $this->getJson("/api/subscriptions/{$subscription->id}");

        $response->assertForbidden();
    }

    public function test_can_cancel_active_subscription(): void
    {
        $subscription = Subscription::factory()->create([
            'user_id'       => $this->user->id,
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'status'        => SubscriptionStatus::Active,
        ]);

        $response = $this->postJson("/api/subscriptions/{$subscription->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', 'canceled');

        $this->assertDatabaseHas('subscriptions', [
            'id'     => $subscription->id,
            'status' => 'canceled',
        ]);
    }

    public function test_can_cancel_trialing_subscription(): void
    {
        $subscription = Subscription::factory()->trialing()->create([
            'user_id'       => $this->user->id,
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
        ]);

        $response = $this->postJson("/api/subscriptions/{$subscription->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', 'canceled');
    }

    public function test_cannot_cancel_already_canceled_subscription(): void
    {
        $subscription = Subscription::factory()->canceled()->create([
            'user_id'       => $this->user->id,
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
        ]);

        $response = $this->postJson("/api/subscriptions/{$subscription->id}/cancel");

        $response->assertStatus(422);
    }
}
