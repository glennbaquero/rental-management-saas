<?php

namespace App\Models;

use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant
{
    use HasDomains;

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'company_name',
            'company_email',
            'company_phone',
            'address',
            'logo',
            'timezone',
            'currency',
            'subscription_status',
        ];
    }
}
