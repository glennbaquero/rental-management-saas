<?php

namespace App\Models;

use App\Enums\LeaseDepositStatus;
use App\Enums\LeaseDepositType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $lease_id
 * @property LeaseDepositType $type
 * @property float $amount
 * @property Carbon|null $payment_date
 * @property LeaseDepositStatus $status
 * @property float|null $refund_amount
 * @property float|null $deduction_amount
 * @property Carbon|null $refund_date
 * @property string|null $notes
 * @property string|null $created_by
 */
#[Fillable([
    'lease_id', 'type', 'amount', 'payment_date', 'status',
    'refund_amount', 'deduction_amount', 'refund_date', 'notes', 'created_by',
])]
class LeaseDeposit extends Model
{
    use HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type'             => LeaseDepositType::class,
            'status'           => LeaseDepositStatus::class,
            'payment_date'     => 'date',
            'refund_date'      => 'date',
            'amount'           => 'decimal:2',
            'refund_amount'    => 'decimal:2',
            'deduction_amount' => 'decimal:2',
        ];
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
