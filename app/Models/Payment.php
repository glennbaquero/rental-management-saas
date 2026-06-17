<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string|null $invoice_id
 * @property string $lease_id
 * @property string $rental_tenant_id
 * @property string $payment_number
 * @property float $amount
 * @property PaymentMethod $payment_method
 * @property string|null $reference_number
 * @property Carbon $payment_date
 * @property string|null $notes
 * @property string|null $received_by
 * @property PaymentStatus $status
 * @property string|null $created_by
 */
#[Fillable(['invoice_id', 'lease_id', 'rental_tenant_id', 'payment_number', 'amount', 'payment_method', 'reference_number', 'payment_date', 'notes', 'received_by', 'status', 'created_by'])]
class Payment extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'payment_date' => 'date',
            'amount' => 'decimal:2',
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

    public function rentalTenant(): BelongsTo
    {
        return $this->belongsTo(RentalTenant::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
