<?php

namespace App\Models;

use App\Enums\BillingCycle;
use App\Enums\LeaseStatus;
use App\Enums\LeaseType;
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
 * @property string|null $building_id
 * @property string $rental_tenant_id
 * @property string $lease_number
 * @property LeaseType $lease_type
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property Carbon|null $move_in_date
 * @property Carbon|null $move_out_date
 * @property float $rent_amount
 * @property float $deposit_amount
 * @property float $advance_payment
 * @property float $utility_deposit
 * @property float $parking_fee
 * @property float $other_charges
 * @property string $currency
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
#[Fillable([
    'unit_id', 'building_id', 'rental_tenant_id', 'lease_number', 'lease_type',
    'start_date', 'end_date', 'move_in_date', 'move_out_date',
    'rent_amount', 'deposit_amount', 'advance_payment', 'utility_deposit', 'parking_fee', 'other_charges', 'currency',
    'deposit_paid', 'deposit_paid_date',
    'billing_day', 'billing_cycle', 'generate_days_before', 'issue_date_offset',
    'status', 'termination_date', 'termination_reason', 'notes', 'created_by',
])]
class Lease extends Model
{
    use HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status'               => LeaseStatus::class,
            'lease_type'           => LeaseType::class,
            'billing_cycle'        => BillingCycle::class,
            'start_date'           => 'date',
            'end_date'             => 'date',
            'move_in_date'         => 'date',
            'move_out_date'        => 'date',
            'deposit_paid_date'    => 'date',
            'termination_date'     => 'date',
            'deposit_paid'         => 'boolean',
            'rent_amount'          => 'decimal:2',
            'deposit_amount'       => 'decimal:2',
            'advance_payment'      => 'decimal:2',
            'utility_deposit'      => 'decimal:2',
            'parking_fee'          => 'decimal:2',
            'other_charges'        => 'decimal:2',
            'generate_days_before' => 'integer',
            'issue_date_offset'    => 'integer',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
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

    public function deposits(): HasMany
    {
        return $this->hasMany(LeaseDeposit::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(LeaseHistory::class)->orderByDesc('occurred_at');
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

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->getDaysRemainingAttribute() <= $days && $this->getDaysRemainingAttribute() > 0;
    }

    public function getDaysRemainingAttribute(): int
    {
        return (int) now()->diffInDays($this->end_date, false);
    }

    public function getDaysUntilExpiryAttribute(): int
    {
        return $this->getDaysRemainingAttribute();
    }

    public function getMonthsRemainingAttribute(): int
    {
        return (int) now()->diffInMonths($this->end_date, false);
    }

    public function getProgressPercentageAttribute(): float
    {
        $total = $this->start_date->diffInDays($this->end_date);
        if ($total === 0) {
            return 100.0;
        }
        $elapsed = $this->start_date->diffInDays(now());

        return (float) min(100, max(0, round(($elapsed / $total) * 100, 1)));
    }

    public function getTotalMonthlyChargesAttribute(): float
    {
        return (float) (
            $this->rent_amount +
            $this->parking_fee +
            $this->other_charges
        );
    }
}
