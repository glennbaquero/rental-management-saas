<?php

namespace App\Models;

use App\Enums\PropertyType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $name
 * @property string $address
 * @property string $city
 * @property string|null $state
 * @property string|null $zip
 * @property string $country
 * @property PropertyType $type
 * @property string|null $description
 * @property string|null $photo
 * @property string|null $created_by
 */
#[Fillable(['name', 'address', 'city', 'state', 'zip', 'country', 'type', 'description', 'photo', 'created_by'])]
class Property extends Model
{
    use HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => PropertyType::class,
        ];
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'amenity_property');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function getAvailableUnitsCountAttribute(): int
    {
        return $this->units()->where('status', 'available')->count();
    }
}
