<?php

namespace App\Enums;

enum MaintenanceStatus: string
{
    case Open = 'open';
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case WaitingForParts = 'waiting_for_parts';
    case OnHold = 'on_hold';
    case Resolved = 'resolved';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Open => 'Open',
            self::Assigned => 'Assigned',
            self::InProgress => 'In Progress',
            self::WaitingForParts => 'Waiting for Parts',
            self::OnHold => 'On Hold',
            self::Resolved => 'Resolved',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Open => 'blue',
            self::Assigned => 'purple',
            self::InProgress => 'yellow',
            self::WaitingForParts => 'orange',
            self::OnHold => 'gray',
            self::Resolved => 'teal',
            self::Completed => 'green',
            self::Cancelled => 'red',
        };
    }
}
