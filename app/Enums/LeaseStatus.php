<?php

namespace App\Enums;

enum LeaseStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Active = 'active';
    case ExpiringSoon = 'expiring_soon';
    case Expired = 'expired';
    case Renewed = 'renewed';
    case Terminated = 'terminated';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Draft => 'Draft',
            self::Pending => 'Pending',
            self::Active => 'Active',
            self::ExpiringSoon => 'Expiring Soon',
            self::Expired => 'Expired',
            self::Renewed => 'Renewed',
            self::Terminated => 'Terminated',
            self::Cancelled => 'Cancelled',
        };
    }
}
