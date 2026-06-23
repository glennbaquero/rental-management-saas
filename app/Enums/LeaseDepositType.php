<?php

namespace App\Enums;

enum LeaseDepositType: string
{
    case Security = 'security';
    case Advance = 'advance';
    case Utility = 'utility';

    public function label(): string
    {
        return match($this) {
            self::Security => 'Security Deposit',
            self::Advance => 'Advance Rent',
            self::Utility => 'Utility Deposit',
        };
    }
}
