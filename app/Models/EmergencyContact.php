<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $rental_tenant_id
 * @property string $name
 * @property string $relationship
 * @property string $phone
 * @property string|null $email
 * @property string|null $address
 */
#[Fillable(['rental_tenant_id', 'name', 'relationship', 'phone', 'email', 'address'])]
class EmergencyContact extends Model
{
    use HasUuids;

    public function rentalTenant(): BelongsTo
    {
        return $this->belongsTo(RentalTenant::class);
    }
}
