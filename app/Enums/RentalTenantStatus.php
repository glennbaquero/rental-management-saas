<?php

namespace App\Enums;

enum RentalTenantStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Blacklisted = 'blacklisted';

    public function label(): string
    {
        return match($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Blacklisted => 'Blacklisted',
        };
    }
}
