<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trialing = 'trialing';
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';

  
    public function isAccessible(): bool
    {
        return match ($this) {
            self::Trialing, self::Active, self::PastDue => true,
            self::Canceled => false,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Trialing => 'Trialing',
            self::Active   => 'Active',
            self::PastDue  => 'Past Due',
            self::Canceled => 'Canceled',
        };
    }

    /**
     * Allowed transitions from this state.
     *
     * @return self[]
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Trialing => [self::Active, self::Canceled],
            self::Active   => [self::PastDue, self::Canceled],
            self::PastDue  => [self::Active, self::Canceled],
            self::Canceled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }
}
