<?php

namespace App\Enums;

enum LeaseTerminationReason: string
{
    case TenantRequest = 'tenant_request';
    case NonPayment = 'non_payment';
    case ViolationOfAgreement = 'violation_of_agreement';
    case PropertySold = 'property_sold';
    case Other = 'other';

    public function label(): string
    {
        return match($this) {
            self::TenantRequest => 'Tenant Request',
            self::NonPayment => 'Non-Payment',
            self::ViolationOfAgreement => 'Violation of Agreement',
            self::PropertySold => 'Property Sold',
            self::Other => 'Other',
        };
    }
}
