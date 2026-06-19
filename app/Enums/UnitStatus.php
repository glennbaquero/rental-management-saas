<?php

namespace App\Enums;

enum UnitStatus: string
{
    case Available   = 'available';
    case Occupied    = 'occupied';
    case Reserved    = 'reserved';
    case Maintenance = 'maintenance';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match($this) {
            self::Available   => 'Available',
            self::Occupied    => 'Occupied',
            self::Reserved    => 'Reserved',
            self::Maintenance => 'Under Maintenance',
            self::Unavailable => 'Unavailable',
        };
    }
}
