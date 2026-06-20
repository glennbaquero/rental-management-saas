<?php

namespace App\Enums;

enum TenantFileType: string
{
    case LeaseAgreement = 'lease_agreement';
    case ProofOfIncome = 'proof_of_income';
    case EmploymentCertificate = 'employment_certificate';
    case UtilityBill = 'utility_bill';
    case PoliceClearance = 'police_clearance';
    case Other = 'other';

    public function label(): string
    {
        return match($this) {
            self::LeaseAgreement => 'Lease Agreement',
            self::ProofOfIncome => 'Proof of Income',
            self::EmploymentCertificate => 'Employment Certificate',
            self::UtilityBill => 'Utility Bill',
            self::PoliceClearance => 'Police Clearance',
            self::Other => 'Other',
        };
    }
}
