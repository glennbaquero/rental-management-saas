<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\WebhookEventStatus;
use App\Models\StripeWebhookEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WebhookRepository
{
    public function findByStripeEventId(string $stripeEventId): ?StripeWebhookEvent
    {
        return StripeWebhookEvent::where('stripe_event_id', $stripeEventId)->first();
    }

    public function createOrIgnore(array $data): StripeWebhookEvent
    {
        DB::table('stripe_webhook_events')->insertOrIgnore([
            'id'              => (string) \Illuminate\Support\Str::uuid(),
            'stripe_event_id' => $data['stripe_event_id'],
            'event_type'      => $data['event_type'],
            'tenant_id'       => $data['tenant_id'] ?? null,
            'payload'         => json_encode($data['payload']),
            'status'          => WebhookEventStatus::Pending->value,
            'attempts'        => 0,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return StripeWebhookEvent::where('stripe_event_id', $data['stripe_event_id'])->firstOrFail();
    }

    public function markProcessing(StripeWebhookEvent $event): void
    {
        $event->update(['status' => WebhookEventStatus::Processing]);
    }

    public function markProcessed(StripeWebhookEvent $event): void
    {
        $event->update([
            'status'       => WebhookEventStatus::Processed,
            'processed_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markFailed(StripeWebhookEvent $event, string $error): void
    {
        $event->update([
            'status'        => WebhookEventStatus::Failed,
            'error_message' => $error,
        ]);
    }

    public function markPendingForRetry(StripeWebhookEvent $event): void
    {
        $event->update([
            'status'        => WebhookEventStatus::Pending,
            'error_message' => null,
        ]);
    }

    public function incrementAttempts(StripeWebhookEvent $event): void
    {
        $event->increment('attempts');
        $event->refresh();
    }

    public function getPaginatedForMonitor(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = StripeWebhookEvent::with('tenant')
            ->orderByDesc('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['event_type'])) {
            $query->where('event_type', 'like', "%{$filters['event_type']}%");
        }

        if (! empty($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }

        if (! empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
