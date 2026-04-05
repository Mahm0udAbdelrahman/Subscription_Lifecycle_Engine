<?php

namespace App\Services\Api;

use App\Enums\BillingCycle;
use App\Enums\Currency;
use App\Enums\SubscriptionStatus;
use App\Events\SubscriptionStateChanged;
use App\Exceptions\InvalidStateTransitionException;
use App\Exceptions\SubscriptionException;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    
    public const GRACE_PERIOD_DAYS = 3;

  
    public function subscribe(User $user, Plan $plan, BillingCycle $billingCycle, Currency $currency): Subscription
    {
        if ($user->hasAccessibleSubscription()) {
            throw SubscriptionException::alreadySubscribed();
        }

        if (! $plan->is_active) {
            throw SubscriptionException::planNotActive();
        }

        $planPrice = $plan->prices()
            ->active()
            ->forCycleAndCurrency($billingCycle, $currency)
            ->first();

        if (! $planPrice) {
            throw SubscriptionException::priceNotFound();
        }

        return DB::transaction(function () use ($user, $plan, $planPrice, $billingCycle, $currency) {
            $now = Carbon::now();
            $hasTrial = $plan->hasTrial();

            $subscription = Subscription::create([
                'user_id'       => $user->id,
                'plan_id'       => $plan->id,
                'plan_price_id' => $planPrice->id,
                'status'        => $hasTrial ? SubscriptionStatus::Trialing : SubscriptionStatus::Active,
                'trial_ends_at' => $hasTrial ? $now->copy()->addDays($plan->trial_period_days) : null,
                'starts_at'     => $now,
                'ends_at'       => $hasTrial
                    ? $now->copy()->addDays($plan->trial_period_days)->addDays($billingCycle->days())
                    : $now->copy()->addDays($billingCycle->days()),
                'price'         => $planPrice->price,
                'currency'      => $currency,
                'billing_cycle' => $billingCycle,
            ]);

            SubscriptionStateChanged::dispatch(
                $subscription,
                'none',
                $subscription->status->value,
                $hasTrial ? 'New subscription with trial' : 'New subscription without trial'
            );

            return $subscription;
        });
    }

   
    public function cancel(Subscription $subscription): Subscription
    {
        $from = $subscription->status;

        if (! $from->canTransitionTo(SubscriptionStatus::Canceled)) {
            throw InvalidStateTransitionException::make($from, SubscriptionStatus::Canceled);
        }

        $subscription->update([
            'status'      => SubscriptionStatus::Canceled,
            'canceled_at' => Carbon::now(),
        ]);

        SubscriptionStateChanged::dispatch(
            $subscription,
            $from->value,
            SubscriptionStatus::Canceled->value,
            'User requested cancellation'
        );

        return $subscription->fresh();
    }

   
    public function transitionToPastDue(Subscription $subscription): Subscription
    {
        $from = $subscription->status;

        if (! $from->canTransitionTo(SubscriptionStatus::PastDue)) {
            throw InvalidStateTransitionException::make($from, SubscriptionStatus::PastDue);
        }

        $subscription->update([
            'status'                => SubscriptionStatus::PastDue,
            'grace_period_ends_at'  => Carbon::now()->addDays(self::GRACE_PERIOD_DAYS),
        ]);

        SubscriptionStateChanged::dispatch(
            $subscription,
            $from->value,
            SubscriptionStatus::PastDue->value,
            'Payment failed – grace period started'
        );

        return $subscription->fresh();
    }

   
    public function reactivate(Subscription $subscription): Subscription
    {
        $from = $subscription->status;

        if (! $from->canTransitionTo(SubscriptionStatus::Active)) {
            throw InvalidStateTransitionException::make($from, SubscriptionStatus::Active);
        }

        $subscription->update([
            'status'                => SubscriptionStatus::Active,
            'grace_period_ends_at'  => null,
        ]);

        SubscriptionStateChanged::dispatch(
            $subscription,
            $from->value,
            SubscriptionStatus::Active->value,
            'Subscription reactivated after successful payment'
        );

        return $subscription->fresh();
    }

    
    public function activate(Subscription $subscription): Subscription
    {
        $from = $subscription->status;

        if (! $from->canTransitionTo(SubscriptionStatus::Active)) {
            throw InvalidStateTransitionException::make($from, SubscriptionStatus::Active);
        }

        $subscription->update([
            'status'        => SubscriptionStatus::Active,
            'trial_ends_at' => null,
        ]);

        SubscriptionStateChanged::dispatch(
            $subscription,
            $from->value,
            SubscriptionStatus::Active->value,
            'Trial ended – subscription activated'
        );

        return $subscription->fresh();
    }

   
    public function processExpiredTrials(): int
    {
        $expired = Subscription::trialing()
            ->where('trial_ends_at', '<=', Carbon::now())
            ->get();

        $count = 0;

        foreach ($expired as $subscription) {
            $subscription->update([
                'status'      => SubscriptionStatus::Canceled,
                'canceled_at' => Carbon::now(),
            ]);

            SubscriptionStateChanged::dispatch(
                $subscription,
                SubscriptionStatus::Trialing->value,
                SubscriptionStatus::Canceled->value,
                'Trial expired without payment'
            );

            $count++;
        }

        return $count;
    }

   
    public function processExpiredGracePeriods(): int
    {
        $expired = Subscription::pastDue()
            ->where('grace_period_ends_at', '<=', Carbon::now())
            ->get();

        $count = 0;

        foreach ($expired as $subscription) {
            $subscription->update([
                'status'      => SubscriptionStatus::Canceled,
                'canceled_at' => Carbon::now(),
            ]);

            SubscriptionStateChanged::dispatch(
                $subscription,
                SubscriptionStatus::PastDue->value,
                SubscriptionStatus::Canceled->value,
                'Grace period expired without payment'
            );

            $count++;
        }

        return $count;
    }
}
