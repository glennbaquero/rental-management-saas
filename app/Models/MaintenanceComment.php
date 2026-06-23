<?php

namespace App\Models;

use App\Enums\MaintenanceCommentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $ticket_id
 * @property string|null $user_id
 * @property MaintenanceCommentType $comment_type
 * @property string $body
 * @property bool $is_pinned
 */
#[Fillable(['ticket_id', 'user_id', 'comment_type', 'body', 'is_pinned'])]
class MaintenanceComment extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'comment_type' => MaintenanceCommentType::class,
            'is_pinned'    => 'boolean',
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

    public function attachments(): HasMany
    {
        return $this->hasMany(MaintenanceAttachment::class, 'comment_id');
    }
}
