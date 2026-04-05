<?php

namespace App\Exceptions;

use App\Enums\SubscriptionStatus;
use Exception;

class InvalidStateTransitionException extends Exception
{
    public static function make(SubscriptionStatus $from, SubscriptionStatus $to): self
    {
        return new self("Cannot transition subscription from {$from->value} to {$to->value}.");
    }
}
