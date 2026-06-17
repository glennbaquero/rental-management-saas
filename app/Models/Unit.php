<?php

namespace App\Models;

use App\Enums\UnitStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $property_id
 * @property string|null $unit_type_id
 * @property string $unit_number
 * @property string|null $floor
 * @property float|null $area_sqm
 * @property int $bedrooms
 * @property int $bathrooms
 * @property float $rent_amount
 * @property float $deposit_amount
 * @property UnitStatus $status
 * @property string|null $notes
 */
#[Fillable(['property_id', 'unit_type_id', 'unit_number', 'floor', 'area_sqm', 'bedrooms', 'bathrooms', 'rent_amount', 'deposit_amount', 'status', 'notes'])]
class Unit extends Model
{
    use HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => UnitStatus::class,
            'rent_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'area_sqm' => 'decimal:2',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function unitType(): BelongsTo
    {
        return $this->belongsTo(UnitType::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function activeLeases(): HasMany
    {
        return $this->hasMany(Lease::class)->where('status', 'active');
    }

    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 'amenity_unit');
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function isAvailable(): bool
    {
        return $this->status === UnitStatus::Available;
    }
}
