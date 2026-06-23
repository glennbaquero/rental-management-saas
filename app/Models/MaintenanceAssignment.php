<?php

namespace App\Models;

use App\Enums\MaintenanceAssigneeType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $ticket_id
 * @property string|null $user_id
 * @property MaintenanceAssigneeType $assignee_type
 * @property string|null $contractor_name
 * @property string|null $contractor_contact
 * @property Carbon $assigned_date
 * @property Carbon|null $estimated_completion
 * @property Carbon|null $actual_completion
 * @property string|null $remarks
 * @property bool $is_primary
 * @property string|null $created_by
 */
#[Fillable([
    'ticket_id', 'user_id', 'assignee_type', 'contractor_name', 'contractor_contact',
    'assigned_date', 'estimated_completion', 'actual_completion', 'remarks', 'is_primary', 'created_by',
])]
class MaintenanceAssignment extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'assignee_type'       => MaintenanceAssigneeType::class,
            'assigned_date'       => 'date',
            'estimated_completion' => 'date',
            'actual_completion'   => 'date',
            'is_primary'          => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
