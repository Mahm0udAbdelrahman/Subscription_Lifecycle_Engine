<?php

namespace App\Listeners;

use App\Events\SubscriptionStateChanged;
use Illuminate\Support\Facades\Log;

class LogSubscriptionStateChange
{
    public function handle(SubscriptionStateChanged $event): void
    {
        Log::info('Subscription state changed', [
            'subscription_id' => $event->subscription->id,
            'user_id'         => $event->subscription->user_id,
            'from'            => $event->fromStatus,
            'to'              => $event->toStatus,
            'reason'          => $event->reason,
        ]);
    }
}
