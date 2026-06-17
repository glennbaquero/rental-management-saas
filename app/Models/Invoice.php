<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $lease_id
 * @property string $invoice_number
 * @property InvoiceType $type
 * @property InvoiceStatus $status
 * @property Carbon|null $billing_period_start
 * @property Carbon|null $billing_period_end
 * @property Carbon $due_date
 * @property float $subtotal
 * @property float $tax_amount
 * @property float $late_fee_amount
 * @property float $discount_amount
 * @property float $total_amount
 * @property float $paid_amount
 * @property float $balance_due
 * @property string|null $notes
 * @property Carbon|null $sent_at
 * @property Carbon|null $paid_at
 * @property Carbon|null $voided_at
 * @property string|null $created_by
 */
#[Fillable(['lease_id', 'invoice_number', 'type', 'status', 'billing_period_start', 'billing_period_end', 'due_date', 'subtotal', 'tax_amount', 'late_fee_amount', 'discount_amount', 'total_amount', 'paid_amount', 'balance_due', 'notes', 'sent_at', 'paid_at', 'voided_at', 'created_by'])]
class Invoice extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'status' => InvoiceStatus::class,
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'late_fee_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        return $this->due_date->isPast()
            && in_array($this->status, [InvoiceStatus::Sent, InvoiceStatus::Partial]);
    }
}
