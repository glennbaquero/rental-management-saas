<?php

namespace App\Enums;

enum IdDocumentType: string
{
    case NationalId = 'national_id';
    case Passport = 'passport';
    case DriversLicense = 'drivers_license';
    case Sss = 'sss';
    case Tin = 'tin';
    case ResidencePermit = 'residence_permit';
    case Other = 'other';

    public function label(): string
    {
        return match($this) {
            self::NationalId => 'National ID',
            self::Passport => 'Passport',
            self::DriversLicense => "Driver's License",
            self::Sss => 'SSS ID',
            self::Tin => 'TIN ID',
            self::ResidencePermit => 'Residence Permit',
            self::Other => 'Other',
        };
    }
}
