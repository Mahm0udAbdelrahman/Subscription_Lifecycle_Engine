<?php

namespace App\Services\Api;

use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(protected SubscriptionService $subscriptionService) {}

    public function recordPayment(Subscription $subscription, float $amount, string $currency, PaymentStatus $status, ?array $metadata = null): Payment
    {
        return DB::transaction(function () use ($subscription, $amount, $currency, $status, $metadata) {
            $payment = Payment::create([
                'subscription_id' => $subscription->id,
                'amount'          => $amount,
                'currency'        => $currency,
                'status'          => $status,
                'transaction_id'  => (string) Str::uuid(),
                'metadata'        => $metadata,
                'paid_at'         => $status === PaymentStatus::Succeeded ? Carbon::now() : null,
            ]);

            $this->handlePaymentStateTransition($subscription, $status);

            return $payment;
        });
    }

   
    protected function handlePaymentStateTransition(Subscription $subscription, PaymentStatus $paymentStatus): void
    {
        $subscription->refresh();

        match (true) {
            $paymentStatus === PaymentStatus::Succeeded
                && $subscription->status === SubscriptionStatus::Trialing
                => $this->subscriptionService->activate($subscription),

            $paymentStatus === PaymentStatus::Succeeded
                && $subscription->status === SubscriptionStatus::PastDue
                => $this->subscriptionService->reactivate($subscription),

            $paymentStatus === PaymentStatus::Failed
                && $subscription->status === SubscriptionStatus::Active
                => $this->subscriptionService->transitionToPastDue($subscription),

            default => null,  
        };
    }
}
