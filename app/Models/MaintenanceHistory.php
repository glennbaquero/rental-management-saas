<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $ticket_id
 * @property string $event_type
 * @property string $description
 * @property array|null $metadata
 * @property Carbon $occurred_at
 * @property string|null $created_by
 */
#[Fillable(['ticket_id', 'event_type', 'description', 'metadata', 'occurred_at', 'created_by'])]
class MaintenanceHistory extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'metadata'    => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'ticket_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function record(
        MaintenanceTicket $ticket,
        string $eventType,
        string $description,
        array $metadata = [],
        ?string $userId = null
    ): self {
        return self::create([
            'ticket_id'   => $ticket->id,
            'event_type'  => $eventType,
            'description' => $description,
            'metadata'    => $metadata ?: null,
            'occurred_at' => now(),
            'created_by'  => $userId,
        ]);
    }
}
