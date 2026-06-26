<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stripe;

use App\Http\Controllers\Controller;
use App\Jobs\Stripe\ProcessStripeWebhookJob;
use App\Repositories\WebhookRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly WebhookRepository $webhookRepo,
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $rawBody  = $request->getContent();
        $sig      = $request->header('Stripe-Signature', '');
        $secret   = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($rawBody, $sig, $secret);
        } catch (SignatureVerificationException $e) {
            Log::channel('stripe')->warning('Webhook signature verification failed', [
                'ip'    => $request->ip(),
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Invalid signature.'], 400);
        }

        $tenantId = $event->data->object->metadata['organization_id']
            ?? $event->data->object->metadata['tenant_id']
            ?? null;

        $webhookEvent = $this->webhookRepo->createOrIgnore([
            'stripe_event_id' => $event->id,
            'event_type'      => $event->type,
            'tenant_id'       => $tenantId,
            'payload'         => json_decode($rawBody, true),
        ]);

        if ($webhookEvent->isAlreadyProcessed()) {
            Log::channel('stripe')->info('Duplicate webhook received, skipping dispatch', [
                'stripe_event_id' => $event->id,
            ]);

            return response()->json(['received' => true]);
        }

        ProcessStripeWebhookJob::dispatch($webhookEvent->id, $event->id);

        Log::channel('stripe')->info('Webhook received and queued', [
            'stripe_event_id' => $event->id,
            'event_type'      => $event->type,
            'tenant_id'       => $tenantId,
        ]);

        return response()->json(['received' => true]);
    }
}
