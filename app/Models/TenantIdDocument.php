<?php

namespace App\Models;

use App\Enums\DocumentVerificationStatus;
use App\Enums\IdDocumentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $id
 * @property string $rental_tenant_id
 * @property IdDocumentType $type
 * @property string|null $document_number
 * @property string|null $issued_by
 * @property Carbon|null $issued_date
 * @property Carbon|null $expiry_date
 * @property string|null $file_path
 * @property string|null $front_image
 * @property string|null $back_image
 * @property DocumentVerificationStatus $verification_status
 */
#[Fillable([
    'rental_tenant_id',
    'type',
    'document_number',
    'issued_by',
    'issued_date',
    'expiry_date',
    'file_path',
    'front_image',
    'back_image',
    'verification_status',
])]
class TenantIdDocument extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'type'                => IdDocumentType::class,
            'verification_status' => DocumentVerificationStatus::class,
            'issued_date'         => 'date',
            'expiry_date'         => 'date',
        ];
    }

    public function getFrontImageUrlAttribute(): ?string
    {
        return $this->front_image
            ? Storage::disk('public')->url($this->front_image)
            : null;
    }

    public function getBackImageUrlAttribute(): ?string
    {
        return $this->back_image
            ? Storage::disk('public')->url($this->back_image)
            : null;
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function rentalTenant(): BelongsTo
    {
        return $this->belongsTo(RentalTenant::class);
    }
}
