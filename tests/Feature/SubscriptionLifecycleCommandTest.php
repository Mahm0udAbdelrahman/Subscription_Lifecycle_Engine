<?php

namespace Tests\Feature;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionLifecycleCommandTest extends TestCase
{
    use RefreshDatabase;

    private Plan $plan;
    private PlanPrice $planPrice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::factory()->create();
        $this->planPrice = PlanPrice::factory()->create([
            'plan_id'       => $this->plan->id,
            'billing_cycle' => 'monthly',
            'currency'      => 'USD',
            'price'         => 29.99,
        ]);
    }

    public function test_command_cancels_expired_trials(): void
    {
       
        $subscription = Subscription::factory()->expiredTrial()->create([
            'user_id'       => User::factory(),
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
        ]);

        $this->artisan('subscription:process-lifecycle')
            ->assertSuccessful();

        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::Canceled, $subscription->status);
        $this->assertNotNull($subscription->canceled_at);
    }

    public function test_command_does_not_cancel_active_trials(): void
    {
        
        $subscription = Subscription::factory()->trialing()->create([
            'user_id'       => User::factory(),
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
        ]);

        $this->artisan('subscription:process-lifecycle')
            ->assertSuccessful();

        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::Trialing, $subscription->status);
    }

    public function test_command_cancels_expired_grace_periods(): void
    {
       
        $subscription = Subscription::factory()->expiredGracePeriod()->create([
            'user_id'       => User::factory(),
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
        ]);

        $this->artisan('subscription:process-lifecycle')
            ->assertSuccessful();

        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::Canceled, $subscription->status);
        $this->assertNotNull($subscription->canceled_at);
    }

    public function test_command_does_not_cancel_active_grace_periods(): void
    {
        
        $subscription = Subscription::factory()->pastDue()->create([
            'user_id'       => User::factory(),
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
        ]);

        $this->artisan('subscription:process-lifecycle')
            ->assertSuccessful();

        $subscription->refresh();
        $this->assertEquals(SubscriptionStatus::PastDue, $subscription->status);
    }

    public function test_command_processes_multiple_subscriptions(): void
    {
        
        Subscription::factory()->expiredTrial()->count(2)->create([
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
        ]);

        
        Subscription::factory()->expiredGracePeriod()->count(3)->create([
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
        ]);

       
        $activeSub = Subscription::factory()->create([
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
            'status'        => SubscriptionStatus::Active,
        ]);

        $this->artisan('subscription:process-lifecycle')
            ->assertSuccessful();

        
        $this->assertDatabaseCount('subscriptions', 6);
        $this->assertEquals(5, Subscription::where('status', SubscriptionStatus::Canceled)->count());

        
        $activeSub->refresh();
        $this->assertEquals(SubscriptionStatus::Active, $activeSub->status);
    }

    public function test_command_outputs_summary_table(): void
    {
        Subscription::factory()->expiredTrial()->create([
            'plan_id'       => $this->plan->id,
            'plan_price_id' => $this->planPrice->id,
        ]);

        $this->artisan('subscription:process-lifecycle')
            ->expectsOutputToContain('Subscription lifecycle processing complete')
            ->assertSuccessful();
    }
}
