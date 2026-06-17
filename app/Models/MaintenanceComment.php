<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $maintenance_request_id
 * @property string|null $user_id
 * @property string $comment
 * @property bool $is_internal
 */
#[Fillable(['maintenance_request_id', 'user_id', 'comment', 'is_internal'])]
class MaintenanceComment extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
