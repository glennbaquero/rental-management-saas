<?php

namespace App\Enums;

enum MaintenancePriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';
    case Emergency = 'emergency';

    public function label(): string
    {
        return match($this) {
            self::Low => 'Low',
            self::Medium => 'Medium',
            self::High => 'High',
            self::Urgent => 'Urgent',
            self::Emergency => 'Emergency',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Low => 'green',
            self::Medium => 'yellow',
            self::High => 'orange',
            self::Urgent => 'red',
            self::Emergency => 'rose',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Low => '🟢',
            self::Medium => '🟡',
            self::High => '🟠',
            self::Urgent => '🔴',
            self::Emergency => '❗',
        };
    }
}
