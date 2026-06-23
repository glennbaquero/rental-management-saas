<?php

namespace App\Enums;

enum LeaseType: string
{
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case FixedTerm = 'fixed_term';
    case Commercial = 'commercial';

    public function label(): string
    {
        return match($this) {
            self::Monthly => 'Monthly',
            self::Yearly => 'Yearly',
            self::FixedTerm => 'Fixed Term',
            self::Commercial => 'Commercial',
        };
    }
}
