<?php

namespace App\Enums;

enum MaintenanceCategory: string
{
    case Plumbing = 'plumbing';
    case Electrical = 'electrical';
    case AirConditioning = 'air_conditioning';
    case ApplianceRepair = 'appliance_repair';
    case InternetWifi = 'internet_wifi';
    case WaterLeak = 'water_leak';
    case Painting = 'painting';
    case PestControl = 'pest_control';
    case StructuralDamage = 'structural_damage';
    case Cleaning = 'cleaning';
    case Security = 'security';
    case Other = 'other';

    public function label(): string
    {
        return match($this) {
            self::Plumbing => 'Plumbing',
            self::Electrical => 'Electrical',
            self::AirConditioning => 'Air Conditioning',
            self::ApplianceRepair => 'Appliance Repair',
            self::InternetWifi => 'Internet / WiFi',
            self::WaterLeak => 'Water Leak',
            self::Painting => 'Painting',
            self::PestControl => 'Pest Control',
            self::StructuralDamage => 'Structural Damage',
            self::Cleaning => 'Cleaning',
            self::Security => 'Security',
            self::Other => 'Other',
        };
    }
}
