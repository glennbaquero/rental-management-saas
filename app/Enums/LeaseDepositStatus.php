<?php

namespace App\Enums;

enum LeaseDepositStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case PartiallyPaid = 'partially_paid';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::PartiallyPaid => 'Partially Paid',
            self::Refunded => 'Refunded',
        };
    }
}
