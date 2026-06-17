<?php

namespace App\Enums;

enum InvoiceType: string
{
    case Rent = 'rent';
    case Deposit = 'deposit';
    case Utility = 'utility';
    case Penalty = 'penalty';
    case Adjustment = 'adjustment';
    case Other = 'other';

    public function label(): string
    {
        return match($this) {
            self::Rent => 'Rent',
            self::Deposit => 'Deposit',
            self::Utility => 'Utility',
            self::Penalty => 'Penalty',
            self::Adjustment => 'Adjustment',
            self::Other => 'Other',
        };
    }
}
