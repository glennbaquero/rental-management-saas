<?php

namespace App\Models;

use App\Enums\RentalTenantStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property Carbon|null $date_of_birth
 * @property string|null $nationality
 * @property string|null $occupation
 * @property string|null $employer
 * @property float|null $monthly_income
 * @property string|null $profile_photo
 * @property RentalTenantStatus $status
 * @property string|null $notes
 * @property string|null $created_by
 */
#[Fillable(['first_name', 'last_name', 'email', 'phone', 'date_of_birth', 'nationality', 'occupation', 'employer', 'monthly_income', 'profile_photo', 'status', 'notes', 'created_by'])]
class RentalTenant extends Model
{
    use HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => RentalTenantStatus::class,
            'date_of_birth' => 'date',
            'monthly_income' => 'decimal:2',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function idDocuments(): HasMany
    {
        return $this->hasMany(TenantIdDocument::class);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(EmergencyContact::class);
    }

    public function leases(): HasMany
    {
        return $this->hasMany(Lease::class);
    }

    public function coTenantLeases(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Lease::class, 'lease_co_tenants');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
