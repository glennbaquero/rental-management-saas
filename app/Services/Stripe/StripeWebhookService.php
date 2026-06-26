<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use App\Events\Stripe\WebhookFailed;
use App\Events\Stripe\WebhookProcessed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Event;

class StripeWebhookService
{
    public function __construct(
        private readonly StripeCustomerService     $customerService,
        private readonly StripeCheckoutService     $checkoutService,
        private readonly StripeSubscriptionService $subscriptionService,
        private readonly StripeInvoiceService      $invoiceService,
        private readonly StripePaymentService      $paymentService,
    ) {}

    public function handle(Event $event, string $webhookEventId): void
    {
        $startTime = microtime(true);
        $tenantId  = $this->extractTenantId($event);

        Log::channel('stripe')->info('Webhook processing started', [
            'stripe_event_id' => $event->id,
            'event_type'      => $event->type,
            'tenant_id'       => $tenantId,
            'webhook_event_id' => $webhookEventId,
        ]);

        DB::transaction(function () use ($event): void {
            $object = $event->data->object;

            match ($event->type) {
                'customer.created'  => $this->customerService->handleCreated($object),
                'customer.updated'  => $this->customerService->handleUpdated($object),
                'customer.deleted'  => $this->customerService->handleDeleted($object),

                'checkout.session.completed'              => $this->checkoutService->handleCompleted($object),
                'checkout.session.async_payment_succeeded' => $this->checkoutService->handleAsyncPaymentSucceeded($object),
                'checkout.session.async_payment_failed'   => $this->checkoutService->handleAsyncPaymentFailed($object),

                'customer.subscription.created'    => $this->subscriptionService->handleCreated($object),
                'customer.subscription.updated'    => $this->subscriptionService->handleUpdated($object),
                'customer.subscription.deleted'    => $this->subscriptionService->handleDeleted($object),
                'customer.subscription.paused'     => $this->subscriptionService->handlePaused($object),
                'customer.subscription.resumed'    => $this->subscriptionService->handleResumed($object),
                'customer.subscription.trial_will_end' => $this->subscriptionService->handleTrialWillEnd($object),

                'invoice.created'              => $this->invoiceService->handleCreated($object),
                'invoice.finalized'            => $this->invoiceService->handleFinalized($object),
                'invoice.payment_succeeded'    => $this->invoiceService->handlePaymentSucceeded($object),
                'invoice.payment_failed'       => $this->invoiceService->handlePaymentFailed($object),
                'invoice.marked_uncollectible' => $this->invoiceService->handleMarkedUncollectible($object),
                'invoice.voided'               => $this->invoiceService->handleVoided($object),

                'payment_intent.succeeded'      => $this->paymentService->handlePaymentIntentSucceeded($object),
                'payment_intent.payment_failed' => $this->paymentService->handlePaymentIntentFailed($object),
                'payment_intent.canceled'       => $this->paymentService->handlePaymentIntentCanceled($object),

                'charge.refunded' => $this->paymentService->handleChargeRefunded($object),

                default => Log::channel('stripe')->debug('Unhandled webhook event type', [
                    'event_type' => $event->type,
                ]),
            };
        });

        $executionMs = round((microtime(true) - $startTime) * 1000, 2);

        Log::channel('stripe')->info('Webhook processing completed', [
            'stripe_event_id'  => $event->id,
            'event_type'       => $event->type,
            'tenant_id'        => $tenantId,
            'execution_time_ms' => $executionMs,
        ]);

        WebhookProcessed::dispatch($event->id, $event->type, $tenantId, $executionMs);
    }

    private function extractTenantId(Event $event): ?string
    {
        $object = $event->data->object;

        return $object->metadata['organization_id']
            ?? $object->metadata['tenant_id']
            ?? null;
    }
}
