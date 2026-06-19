<?php

namespace App\Enums;

enum PropertyStatus: string
{
    case Active           = 'active';
    case UnderMaintenance = 'under_maintenance';
    case Inactive         = 'inactive';

    public function label(): string
    {
        return match($this) {
            self::Active           => 'Active',
            self::UnderMaintenance => 'Under Maintenance',
            self::Inactive         => 'Inactive',
        };
    }
}
