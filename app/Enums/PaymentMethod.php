<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case BankTransfer = 'bank_transfer';
    case GCash = 'gcash';
    case PayMaya = 'paymaya';
    case Stripe = 'stripe';
    case Check = 'check';
    case Other = 'other';

    public function label(): string
    {
        return match($this) {
            self::Cash => 'Cash',
            self::BankTransfer => 'Bank Transfer',
            self::GCash => 'GCash',
            self::PayMaya => 'PayMaya',
            self::Stripe => 'Stripe',
            self::Check => 'Check',
            self::Other => 'Other',
        };
    }
}
