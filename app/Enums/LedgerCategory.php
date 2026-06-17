<?php

namespace App\Enums;

enum LedgerCategory: string
{
    case Rent = 'rent';
    case Deposit = 'deposit';
    case Payment = 'payment';
    case LateFee = 'late_fee';
    case Utility = 'utility';
    case Refund = 'refund';
    case Adjustment = 'adjustment';

    public function label(): string
    {
        return match($this) {
            self::Rent => 'Rent',
            self::Deposit => 'Deposit',
            self::Payment => 'Payment',
            self::LateFee => 'Late Fee',
            self::Utility => 'Utility',
            self::Refund => 'Refund',
            self::Adjustment => 'Adjustment',
        };
    }
}
