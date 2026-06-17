<?php

namespace App\Enums;

enum MaintenanceCategory: string
{
    case Plumbing = 'plumbing';
    case Electrical = 'electrical';
    case Hvac = 'hvac';
    case Appliance = 'appliance';
    case Structural = 'structural';
    case PestControl = 'pest_control';
    case Carpentry = 'carpentry';
    case Other = 'other';

    public function label(): string
    {
        return match($this) {
            self::Plumbing => 'Plumbing',
            self::Electrical => 'Electrical',
            self::Hvac => 'HVAC',
            self::Appliance => 'Appliance',
            self::Structural => 'Structural',
            self::PestControl => 'Pest Control',
            self::Carpentry => 'Carpentry',
            self::Other => 'Other',
        };
    }
}
