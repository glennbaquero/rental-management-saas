<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $lease_id
 * @property Carbon $previous_end_date
 * @property Carbon $new_end_date
 * @property float $new_rent_amount
 * @property Carbon $renewal_date
 * @property string|null $notes
 * @property string|null $created_by
 */
#[Fillable(['lease_id', 'previous_end_date', 'new_end_date', 'new_rent_amount', 'renewal_date', 'notes', 'created_by'])]
class LeaseRenewal extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'previous_end_date' => 'date',
            'new_end_date' => 'date',
            'renewal_date' => 'date',
            'new_rent_amount' => 'decimal:2',
        ];
    }

    public function lease(): BelongsTo
    {
        return $this->belongsTo(Lease::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
