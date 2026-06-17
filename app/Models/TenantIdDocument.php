<?php

namespace App\Models;

use App\Enums\IdDocumentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $rental_tenant_id
 * @property IdDocumentType $type
 * @property string|null $document_number
 * @property string|null $issued_by
 * @property Carbon|null $issued_date
 * @property Carbon|null $expiry_date
 * @property string|null $file_path
 */
#[Fillable(['rental_tenant_id', 'type', 'document_number', 'issued_by', 'issued_date', 'expiry_date', 'file_path'])]
class TenantIdDocument extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'type' => IdDocumentType::class,
            'issued_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public function rentalTenant(): BelongsTo
    {
        return $this->belongsTo(RentalTenant::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}
