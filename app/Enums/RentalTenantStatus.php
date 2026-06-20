<?php

namespace App\Enums;

enum RentalTenantStatus: string
{
    case Prospect = 'prospect';
    case Active = 'active';
    case MovedOut = 'moved_out';
    case Blacklisted = 'blacklisted';

    public function label(): string
    {
        return match($this) {
            self::Prospect => 'Prospect',
            self::Active => 'Active',
            self::MovedOut => 'Moved Out',
            self::Blacklisted => 'Blacklisted',
        };
    }
}
