<?php

declare(strict_types=1);

namespace App\Jobs\Stripe;

use App\Enums\WebhookEventStatus;
use App\Events\Stripe\WebhookFailed;
use App\Repositories\WebhookRepository;
use App\Services\Stripe\StripeWebhookService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Throwable;

class ProcessStripeWebhookJob implements ShouldQueue, ShouldBeUnique
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public int $timeout = 60;

    public function __construct(
        public readonly string $webhookEventId,
        public readonly string $stripeEventId,
    ) {
        $this->onQueue('stripe-webhooks');
    }

    public function uniqueId(): string
    {
        return $this->stripeEventId;
    }

    public function handle(WebhookRepository $webhookRepo, StripeWebhookService $webhookService): void
    {
        $event = $webhookRepo->findByStripeEventId($this->stripeEventId);

        if (! $event) {
            Log::channel('stripe')->error('ProcessStripeWebhookJob: event record not found', [
                'stripe_event_id'  => $this->stripeEventId,
                'webhook_event_id' => $this->webhookEventId,
            ]);
            return;
        }

        if ($event->isAlreadyProcessed()) {
            Log::channel('stripe')->info('ProcessStripeWebhookJob: skipping already processed event', [
                'stripe_event_id' => $this->stripeEventId,
            ]);
            return;
        }

        $webhookRepo->markProcessing($event);
        $webhookRepo->incrementAttempts($event);

        $stripeEvent = Event::constructFrom($event->payload);

        $webhookService->handle($stripeEvent, $event->id);

        $webhookRepo->markProcessed($event);
    }

    public function failed(Throwable $exception): void
    {
        $webhookRepo = app(WebhookRepository::class);
        $event       = $webhookRepo->findByStripeEventId($this->stripeEventId);

        if ($event) {
            $webhookRepo->markFailed($event, $exception->getMessage());

            WebhookFailed::dispatch(
                $this->stripeEventId,
                $event->event_type,
                $event->tenant_id,
                $exception->getMessage(),
                $event->attempts,
            );
        }

        Log::channel('stripe')->error('ProcessStripeWebhookJob: job failed', [
            'stripe_event_id' => $this->stripeEventId,
            'error'           => $exception->getMessage(),
            'trace'           => $exception->getTraceAsString(),
        ]);
    }
}
