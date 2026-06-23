<?php

namespace App\Models;

use App\Enums\MaintenanceCategory;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property string $id
 * @property string $ticket_number
 * @property string $property_id
 * @property string|null $building_id
 * @property string $unit_id
 * @property string|null $rental_tenant_id
 * @property MaintenanceCategory $category
 * @property string $title
 * @property string $description
 * @property MaintenancePriority $priority
 * @property MaintenanceStatus $status
 * @property Carbon|null $preferred_schedule
 * @property Carbon $date_submitted
 * @property Carbon|null $resolved_at
 * @property Carbon|null $completed_at
 * @property string|null $notes
 * @property string|null $created_by
 * @property string|null $updated_by
 */
#[Fillable([
    'ticket_number', 'property_id', 'building_id', 'unit_id', 'rental_tenant_id',
    'category', 'title', 'description', 'priority', 'status',
    'preferred_schedule', 'date_submitted', 'resolved_at', 'completed_at',
    'notes', 'created_by', 'updated_by',
])]
class MaintenanceTicket extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'category'           => MaintenanceCategory::class,
            'priority'           => MaintenancePriority::class,
            'status'             => MaintenanceStatus::class,
            'preferred_schedule' => 'datetime',
            'date_submitted'     => 'datetime',
            'resolved_at'        => 'datetime',
            'completed_at'       => 'datetime',
        ];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function rentalTenant(): BelongsTo
    {
        return $this->belongsTo(RentalTenant::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(MaintenanceAssignment::class, 'ticket_id');
    }

    public function primaryAssignment(): HasOne
    {
        return $this->hasOne(MaintenanceAssignment::class, 'ticket_id')->where('is_primary', true);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(MaintenanceComment::class, 'ticket_id')->orderBy('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MaintenanceAttachment::class, 'ticket_id');
    }

    public function costs(): HasMany
    {
        return $this->hasMany(MaintenanceCost::class, 'ticket_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(MaintenanceHistory::class, 'ticket_id')->orderBy('occurred_at');
    }

    public function rating(): HasOne
    {
        return $this->hasOne(MaintenanceRating::class, 'ticket_id');
    }

    public function getTotalCostAttribute(): float
    {
        return (float) $this->costs()->sum('amount');
    }

    public function getIsOverdueAttribute(): bool
    {
        $primary = $this->primaryAssignment;

        return $primary?->estimated_completion !== null
            && $primary->estimated_completion->isPast()
            && ! in_array($this->status, [
                MaintenanceStatus::Resolved,
                MaintenanceStatus::Completed,
                MaintenanceStatus::Cancelled,
            ]);
    }

    public static function generateTicketNumber(): string
    {
        return DB::transaction(function () {
            $today  = now()->format('Ymd');
            $prefix = "MNT-{$today}-";

            $last = self::withTrashed()
                ->where('ticket_number', 'like', "{$prefix}%")
                ->lockForUpdate()
                ->orderByDesc('ticket_number')
                ->value('ticket_number');

            $sequence = $last ? ((int) substr($last, -5)) + 1 : 1;

            return $prefix . str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
        });
    }
}
