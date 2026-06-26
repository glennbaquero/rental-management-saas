<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\WebhookEventStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $stripe_event_id
 * @property string $event_type
 * @property string|null $tenant_id
 * @property array $payload
 * @property WebhookEventStatus $status
 * @property string|null $error_message
 * @property int $attempts
 * @property \Illuminate\Support\Carbon|null $processed_at
 */
#[Fillable([
    'stripe_event_id',
    'event_type',
    'tenant_id',
    'payload',
    'status',
    'error_message',
    'attempts',
    'processed_at',
])]
class StripeWebhookEvent extends Model
{
    use HasUuids;

    protected $connection = 'mysql';

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'status'       => WebhookEventStatus::class,
            'attempts'     => 'integer',
            'processed_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', WebhookEventStatus::Pending->value);
    }

    public function scopeFailed(Builder $query): void
    {
        $query->where('status', WebhookEventStatus::Failed->value);
    }

    public function isAlreadyProcessed(): bool
    {
        return $this->status === WebhookEventStatus::Processed;
    }
}
