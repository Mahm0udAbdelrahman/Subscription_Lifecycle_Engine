<?php

namespace App\Exceptions;

use Exception;

class SubscriptionException extends Exception
{
    public static function alreadySubscribed(): self
    {
        return new self('User already has an active or trialing subscription.', 422);
    }

    public static function planNotActive(): self
    {
        return new self('The selected plan is not active.', 422);
    }

    public static function priceNotFound(): self
    {
        return new self('No pricing found for the selected cycle and currency.', 422);
    }
}
