<?php

namespace App\Enums;

enum LeaseStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Expired = 'expired';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::Terminated => 'Terminated',
        };
    }
}
