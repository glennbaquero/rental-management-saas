<?php

namespace App\Models;

use App\Enums\LedgerCategory;
use App\Enums\LedgerEntryType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $lease_id
 * @property string $rental_tenant_id
 * @property string $unit_id
 * @property Carbon $entry_date
 * @property LedgerEntryType $type
 * @property LedgerCategory $category
 * @property string $description
 * @property float $amount
 * @property float $balance_after
 * @property string|null $invoice_id
 * @property string|null $payment_id
 * @property string|null $created_by
 */
#[Fillable(['lease_id', 'rental_tenant_id', 'unit_id', 'entry_date', 'type', 'category', 'description', 'amount', 'balance_after', 'invoice_id', 'payment_id', 'created_by'])]
class LedgerEntry extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'type' => LedgerEntryType::class,
            'category' => LedgerCategory::class,
            'entry_date' => 'date',
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function rentalTenant(): BelongsTo
    {
        return $this->belongsTo(RentalTenant::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
