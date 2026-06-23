<?php

namespace App\Enums;

enum MaintenanceCommentType: string
{
    case Internal = 'internal';
    case Tenant = 'tenant';
    case Staff = 'staff';

    public function label(): string
    {
        return match($this) {
            self::Internal => 'Internal Note',
            self::Tenant => 'Tenant Comment',
            self::Staff => 'Staff Comment',
        };
    }
}
