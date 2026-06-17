<?php

namespace App\Models;

use App\Enums\MaintenanceCategory;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $unit_id
 * @property string|null $rental_tenant_id
 * @property string $title
 * @property string $description
 * @property MaintenanceCategory $category
 * @property MaintenancePriority $priority
 * @property MaintenanceStatus $status
 * @property string|null $assigned_to
 * @property float|null $estimated_cost
 * @property float|null $actual_cost
 * @property Carbon|null $scheduled_date
 * @property Carbon|null $resolved_date
 * @property string|null $notes
 * @property string|null $created_by
 */
#[Fillable(['unit_id', 'rental_tenant_id', 'title', 'description', 'category', 'priority', 'status', 'assigned_to', 'estimated_cost', 'actual_cost', 'scheduled_date', 'resolved_date', 'notes', 'created_by'])]
class MaintenanceRequest extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'category' => MaintenanceCategory::class,
            'priority' => MaintenancePriority::class,
            'status' => MaintenanceStatus::class,
            'scheduled_date' => 'date',
            'resolved_date' => 'datetime',
            'estimated_cost' => 'decimal:2',
            'actual_cost' => 'decimal:2',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function rentalTenant(): BelongsTo
    {
        return $this->belongsTo(RentalTenant::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(MaintenanceComment::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [MaintenanceStatus::Open, MaintenanceStatus::InProgress]);
    }
}
