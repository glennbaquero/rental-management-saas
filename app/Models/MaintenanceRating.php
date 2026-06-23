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
 * @property string|null $rental_tenant_id
 * @property int $rating
 * @property string|null $feedback
 * @property bool|null $would_recommend
 * @property Carbon $rated_at
 */
#[Fillable(['ticket_id', 'rental_tenant_id', 'rating', 'feedback', 'would_recommend', 'rated_at'])]
class MaintenanceRating extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'rating'           => 'integer',
            'would_recommend'  => 'boolean',
            'rated_at'         => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'ticket_id');
    }

    public function rentalTenant(): BelongsTo
    {
        return $this->belongsTo(RentalTenant::class);
    }
}
