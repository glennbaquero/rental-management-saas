<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'slug', 'icon', 'category', 'is_system'])]
class Amenity extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'amenity_property');
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'amenity_unit');
    }

    public function scopeForProperty($query)
    {
        return $query->whereIn('category', ['property', 'both']);
    }

    public function scopeForUnit($query)
    {
        return $query->whereIn('category', ['unit', 'both']);
    }
}
