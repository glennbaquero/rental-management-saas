<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $name
 * @property string|null $description
 * @property int|null $max_occupants
 */
#[Fillable(['name', 'description', 'max_occupants'])]
class UnitType extends Model
{
    use HasUuids;

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }
}
