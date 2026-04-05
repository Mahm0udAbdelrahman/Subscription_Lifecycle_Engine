<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');

        $plan = Plan::factory()->create();
        $planPrice = PlanPrice::factory()->create([
            'plan_id'       => $plan->id,
            'billing_cycle' => 'monthly',
            'currency'      => 'USD',
            'price'         => 29.99,
        ]);

        $this->subscription = Subscription::factory()->create([
            'user_id'       => $this->user->id,
            'plan_id'       => $plan->id,
            'plan_price_id' => $planPrice->id,
            'status'        => SubscriptionStatus::Active,
        ]);
    }

    public function test_can_record_successful_payment(): void
    {
        $response = $this->postJson("/api/subscriptions/{$this->subscription->id}/payments", [
            'amount'   => 29.99,
            'currency' => 'USD',
            'status'   => 'succeeded',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.payment.status', 'succeeded')
            ->assertJsonPath('data.payment.amount', '29.99');

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_failed_payment_transitions_active_to_past_due(): void
    {
        $response = $this->postJson("/api/subscriptions/{$this->subscription->id}/payments", [
            'amount'   => 29.99,
            'currency' => 'USD',
            'status'   => 'failed',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subscription.status', 'past_due');

        $this->assertDatabaseHas('subscriptions', [
            'id'     => $this->subscription->id,
            'status' => 'past_due',
        ]);

       
        $this->subscription->refresh();
        $this->assertNotNull($this->subscription->grace_period_ends_at);
    }

    public function test_successful_payment_reactivates_past_due_subscription(): void
    {
      
        $this->subscription->update([
            'status'                => SubscriptionStatus::PastDue,
            'grace_period_ends_at'  => now()->addDays(2),
        ]);

        $response = $this->postJson("/api/subscriptions/{$this->subscription->id}/payments", [
            'amount'   => 29.99,
            'currency' => 'USD',
            'status'   => 'succeeded',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subscription.status', 'active');

        $this->assertDatabaseHas('subscriptions', [
            'id'     => $this->subscription->id,
            'status' => 'active',
        ]);
    }

    public function test_successful_payment_activates_trialing_subscription(): void
    {
        $this->subscription->update([
            'status'        => SubscriptionStatus::Trialing,
            'trial_ends_at' => now()->addDays(7),
        ]);

        $response = $this->postJson("/api/subscriptions/{$this->subscription->id}/payments", [
            'amount'   => 29.99,
            'currency' => 'USD',
            'status'   => 'succeeded',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subscription.status', 'active');
    }

    public function test_can_list_subscription_payments(): void
    {
       
        $this->postJson("/api/subscriptions/{$this->subscription->id}/payments", [
            'amount'   => 29.99,
            'currency' => 'USD',
            'status'   => 'succeeded',
        ]);

        $response = $this->getJson("/api/subscriptions/{$this->subscription->id}/payments");

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_payment_requires_valid_data(): void
    {
        $response = $this->postJson("/api/subscriptions/{$this->subscription->id}/payments", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount', 'currency', 'status']);
    }

    public function test_cannot_record_payment_for_other_users_subscription(): void
    {
        $otherUser = User::factory()->create();
        $otherPlan = Plan::factory()->create();
        $otherPrice = PlanPrice::factory()->create(['plan_id' => $otherPlan->id]);

        $otherSubscription = Subscription::factory()->create([
            'user_id'       => $otherUser->id,
            'plan_id'       => $otherPlan->id,
            'plan_price_id' => $otherPrice->id,
        ]);

        $response = $this->postJson("/api/subscriptions/{$otherSubscription->id}/payments", [
            'amount'   => 29.99,
            'currency' => 'USD',
            'status'   => 'succeeded',
        ]);

        $response->assertForbidden();
    }
}
