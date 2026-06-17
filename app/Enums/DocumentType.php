<?php

namespace App\Enums;

enum DocumentType: string
{
    case LeaseAgreement = 'lease_agreement';
    case IdDocument = 'id_document';
    case PropertyPhoto = 'property_photo';
    case UnitPhoto = 'unit_photo';
    case MaintenancePhoto = 'maintenance_photo';
    case SignedContract = 'signed_contract';
    case Other = 'other';

    public function label(): string
    {
        return match($this) {
            self::LeaseAgreement => 'Lease Agreement',
            self::IdDocument => 'ID Document',
            self::PropertyPhoto => 'Property Photo',
            self::UnitPhoto => 'Unit Photo',
            self::MaintenancePhoto => 'Maintenance Photo',
            self::SignedContract => 'Signed Contract',
            self::Other => 'Other',
        };
    }
}
