<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Succeeded => 'Succeeded',
            self::Failed    => 'Failed',
            self::Refunded  => 'Refunded',
        };
    }
}
