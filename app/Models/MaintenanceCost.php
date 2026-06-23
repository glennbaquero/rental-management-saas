<?php

namespace App\Models;

use App\Enums\MaintenanceCostStatus;
use App\Enums\MaintenanceCostType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $id
 * @property string $ticket_id
 * @property MaintenanceCostType $cost_type
 * @property string $description
 * @property float $amount
 * @property MaintenanceCostStatus $status
 * @property string|null $receipt_path
 * @property string|null $added_by
 * @property string|null $approved_by
 * @property Carbon|null $approved_at
 */
#[Fillable([
    'ticket_id', 'cost_type', 'description', 'amount', 'status',
    'receipt_path', 'added_by', 'approved_by', 'approved_at',
])]
class MaintenanceCost extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'cost_type'   => MaintenanceCostType::class,
            'status'      => MaintenanceCostStatus::class,
            'amount'      => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'ticket_id');
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function getReceiptUrlAttribute(): ?string
    {
        return $this->receipt_path
            ? Storage::disk('public')->url($this->receipt_path)
            : null;
    }
}
