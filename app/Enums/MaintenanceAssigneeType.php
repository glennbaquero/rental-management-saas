<?php

namespace App\Enums;

enum MaintenanceAssigneeType: string
{
    case PropertyManager = 'property_manager';
    case MaintenanceStaff = 'maintenance_staff';
    case ExternalContractor = 'external_contractor';

    public function label(): string
    {
        return match($this) {
            self::PropertyManager => 'Property Manager',
            self::MaintenanceStaff => 'Maintenance Staff',
            self::ExternalContractor => 'External Contractor',
        };
    }
}
