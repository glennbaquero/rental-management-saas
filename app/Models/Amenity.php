<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property string $id
 * @property string $name
 * @property string|null $icon
 * @property string|null $category
 */
#[Fillable(['name', 'icon', 'category'])]
class Amenity extends Model
{
    use HasUuids;

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'amenity_property');
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'amenity_unit');
    }
}
