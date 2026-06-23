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
 * @property string $event_type
 * @property string $description
 * @property array|null $metadata
 * @property Carbon $occurred_at
 * @property string|null $created_by
 */
#[Fillable(['lease_id', 'event_type', 'description', 'metadata', 'occurred_at', 'created_by'])]
class LeaseHistory extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'metadata'    => 'array',
            'occurred_at' => 'datetime',
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

    public static function record(
        Lease $lease,
        string $eventType,
        string $description,
        array $metadata = [],
        ?string $userId = null
    ): self {
        return self::create([
            'lease_id'    => $lease->id,
            'event_type'  => $eventType,
            'description' => $description,
            'metadata'    => $metadata ?: null,
            'occurred_at' => now(),
            'created_by'  => $userId,
        ]);
    }
}
