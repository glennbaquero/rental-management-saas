<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\LeaseStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $unit_id
 * @property string $rental_tenant_id
 * @property string $lease_number
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property float $rent_amount
 * @property float $deposit_amount
 * @property bool $deposit_paid
 * @property Carbon|null $deposit_paid_date
 * @property int $billing_day
 * @property BillingCycle $billing_cycle
 * @property int|null $generate_days_before
 * @property int $issue_date_offset
 * @property LeaseStatus $status
 * @property Carbon|null $termination_date
 * @property string|null $termination_reason
 * @property string|null $notes
 * @property string|null $created_by
 */
#[Fillable(['unit_id', 'rental_tenant_id', 'lease_number', 'start_date', 'end_date', 'rent_amount', 'deposit_amount', 'deposit_paid', 'deposit_paid_date', 'billing_day', 'billing_cycle', 'generate_days_before', 'issue_date_offset', 'status', 'termination_date', 'termination_reason', 'notes', 'created_by'])]
class Lease extends Model
{
    use HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status'               => LeaseStatus::class,
            'billing_cycle'        => BillingCycle::class,
            'start_date'           => 'date',
            'end_date'             => 'date',
            'deposit_paid_date'    => 'date',
            'termination_date'     => 'date',
            'deposit_paid'         => 'boolean',
            'rent_amount'          => 'decimal:2',
            'deposit_amount'       => 'decimal:2',
            'generate_days_before' => 'integer',
            'issue_date_offset'    => 'integer',
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

    public function coTenants(): BelongsToMany
    {
        return $this->belongsToMany(RentalTenant::class, 'lease_co_tenants');
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(LeaseRenewal::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function lateFees(): HasMany
    {
        return $this->hasMany(LateFee::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isActive(): bool
    {
        return $this->status === LeaseStatus::Active;
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        return (int) now()->diffInDays($this->end_date, false);
    }
}
