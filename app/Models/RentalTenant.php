<?php

namespace App\Models;

use App\Enums\CivilStatus;
use App\Enums\Gender;
use App\Enums\RentalTenantStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $id
 * @property string|null $tenant_code
 * @property string $first_name
 * @property string|null $middle_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $alternate_phone
 * @property Carbon|null $date_of_birth
 * @property Gender|null $gender
 * @property CivilStatus|null $civil_status
 * @property string|null $nationality
 * @property string|null $occupation
 * @property string|null $employer
 * @property string|null $employer_address
 * @property string|null $current_address
 * @property string|null $city
 * @property string|null $province
 * @property string|null $country
 * @property string|null $postal_code
 * @property float|null $monthly_income
 * @property string|null $profile_photo
 * @property RentalTenantStatus $status
 * @property string|null $notes
 * @property string|null $created_by
 */
#[Fillable([
    'tenant_code',
    'first_name',
    'middle_name',
    'last_name',
    'email',
    'phone',
    'alternate_phone',
    'date_of_birth',
    'gender',
    'civil_status',
    'nationality',
    'occupation',
    'employer',
    'employer_address',
    'current_address',
    'city',
    'province',
    'country',
    'postal_code',
    'monthly_income',
    'profile_photo',
    'status',
    'notes',
    'created_by',
])]
class RentalTenant extends Model
{
    use HasUuids, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status'       => RentalTenantStatus::class,
            'gender'       => Gender::class,
            'civil_status' => CivilStatus::class,
            'date_of_birth' => 'date',
            'monthly_income' => 'decimal:2',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return collect([$this->first_name, $this->middle_name, $this->last_name])
            ->filter()
            ->implode(' ');
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo
            ? Storage::disk('public')->url($this->profile_photo)
            : null;
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

    public function tenantFiles(): HasMany
    {
        return $this->hasMany(TenantFile::class);
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
