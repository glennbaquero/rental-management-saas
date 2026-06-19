<?php

namespace App\Enums;

enum PropertyType: string
{
    case Apartment   = 'apartment';
    case Condominium = 'condominium';
    case Dormitory   = 'dormitory';
    case House       = 'house';
    case Townhouse   = 'townhouse';
    case Commercial  = 'commercial';
    case MixedUse    = 'mixed_use';

    public function label(): string
    {
        return match($this) {
            self::Apartment   => 'Apartment',
            self::Condominium => 'Condominium',
            self::Dormitory   => 'Dormitory',
            self::House       => 'House',
            self::Townhouse   => 'Townhouse',
            self::Commercial  => 'Commercial',
            self::MixedUse    => 'Mixed Use',
        };
    }
}
