<?php

namespace App\Enums;

enum PropertyType: string
{
    case Residential = 'residential';
    case Commercial = 'commercial';
    case Mixed = 'mixed';

    public function label(): string
    {
        return match($this) {
            self::Residential => 'Residential',
            self::Commercial => 'Commercial',
            self::Mixed => 'Mixed Use',
        };
    }
}
