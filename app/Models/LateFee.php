<?php

namespace App\Models;

use App\Enums\LateFeeType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $invoice_id
 * @property string $lease_id
 * @property LateFeeType $type
 * @property float $rate
 * @property float $amount
 * @property int $days_overdue
 * @property Carbon $applied_at
 * @property string|null $created_by
 */
#[Fillable(['invoice_id', 'lease_id', 'type', 'rate', 'amount', 'days_overdue', 'applied_at', 'created_by'])]
class LateFee extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'type'        => LateFeeType::class,
            'rate'        => 'decimal:2',
            'amount'      => 'decimal:2',
            'days_overdue' => 'integer',
            'applied_at'  => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
