<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\UnitType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PropertyDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUnitTypes();
        $this->seedAmenities();
    }

    private function seedUnitTypes(): void
    {
        $types = [
            ['name' => 'Studio',       'description' => 'Open-plan studio unit',           'max_occupants' => 2],
            ['name' => '1 Bedroom',    'description' => 'One bedroom apartment',            'max_occupants' => 2],
            ['name' => '2 Bedroom',    'description' => 'Two bedroom apartment',            'max_occupants' => 4],
            ['name' => '3 Bedroom',    'description' => 'Three bedroom apartment',          'max_occupants' => 6],
            ['name' => 'Penthouse',    'description' => 'Top-floor luxury penthouse',       'max_occupants' => 8],
            ['name' => 'Office Space', 'description' => 'Commercial office unit',           'max_occupants' => 20],
            ['name' => 'Warehouse',    'description' => 'Industrial warehouse or storage',  'max_occupants' => 10],
        ];

        foreach ($types as $type) {
            UnitType::firstOrCreate(['name' => $type['name']], $type);
        }
    }

    private function seedAmenities(): void
    {
        $amenities = [
            ['name' => 'Swimming Pool',   'category' => 'property', 'icon' => 'waves'],
            ['name' => 'Gym',             'category' => 'property', 'icon' => 'dumbbell'],
            ['name' => 'Elevator',        'category' => 'property', 'icon' => 'arrow-up-down'],
            ['name' => 'Parking',         'category' => 'property', 'icon' => 'car'],
            ['name' => 'Garden',          'category' => 'property', 'icon' => 'trees'],
            ['name' => 'Security',        'category' => 'property', 'icon' => 'shield'],
            ['name' => 'CCTV',            'category' => 'property', 'icon' => 'cctv'],
            ['name' => 'Playground',      'category' => 'property', 'icon' => 'smile'],

            ['name' => 'Air Conditioning', 'category' => 'unit', 'icon' => 'wind'],
            ['name' => 'Refrigerator',     'category' => 'unit', 'icon' => 'box'],
            ['name' => 'WiFi',             'category' => 'unit', 'icon' => 'wifi'],
            ['name' => 'Balcony',          'category' => 'unit', 'icon' => 'building'],
            ['name' => 'Kitchen',          'category' => 'unit', 'icon' => 'utensils'],
            ['name' => 'Water Heater',     'category' => 'unit', 'icon' => 'thermometer'],
            ['name' => 'TV',               'category' => 'unit', 'icon' => 'tv'],
        ];

        foreach ($amenities as $amenity) {
            Amenity::firstOrCreate(
                ['slug' => Str::slug($amenity['name'])],
                [...$amenity, 'slug' => Str::slug($amenity['name']), 'is_system' => true]
            );
        }
    }
}
