<?php

namespace App\Enums;

enum MaintenanceStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::OnHold => 'On Hold',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }
}
