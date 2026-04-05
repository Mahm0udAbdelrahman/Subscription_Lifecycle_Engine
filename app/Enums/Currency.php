<?php

namespace App\Enums;

enum Currency: string
{
    case AED = 'AED';
    case USD = 'USD';
    case EGP = 'EGP';

    public function label(): string
    {
        return match ($this) {
            self::AED => 'UAE Dirham',
            self::USD => 'US Dollar',
            self::EGP => 'Egyptian Pound',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::AED => 'د.إ',
            self::USD => '$',
            self::EGP => 'E£',
        };
    }
}
