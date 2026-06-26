<?php

declare(strict_types=1);

namespace App\Http\Controllers\Stripe;

use App\Enums\WebhookEventStatus;
use App\Http\Controllers\Controller;
use App\Jobs\Stripe\ProcessStripeWebhookJob;
use App\Models\StripeWebhookEvent;
use App\Repositories\WebhookRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WebhookMonitorController extends Controller
{
    public function __construct(
        private readonly WebhookRepository $webhookRepo,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['status', 'event_type', 'date', 'tenant_id']);

        $events = $this->webhookRepo->getPaginatedForMonitor($filters);

        return Inertia::render('stripe/WebhookMonitor', [
            'events'  => $events->through(fn ($event) => [
                'id'              => $event->id,
                'stripe_event_id' => $event->stripe_event_id,
                'event_type'      => $event->event_type,
                'tenant_id'       => $event->tenant_id,
                'tenant_name'     => $event->tenant?->company_name,
                'status'          => $event->status->value,
                'status_label'    => $event->status->label(),
                'status_color'    => $event->status->color(),
                'error_message'   => $event->error_message,
                'attempts'        => $event->attempts,
                'processed_at'    => $event->processed_at?->toISOString(),
                'created_at'      => $event->created_at->toISOString(),
                'payload'         => $event->payload,
            ]),
            'filters' => $filters,
            'statuses' => collect(WebhookEventStatus::cases())->map(fn ($case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ]),
        ]);
    }

    public function retry(StripeWebhookEvent $event): RedirectResponse
    {
        $this->webhookRepo->markPendingForRetry($event);

        ProcessStripeWebhookJob::dispatch($event->id, $event->stripe_event_id);

        return back()->with('toast', [
            'type'    => 'success',
            'message' => "Webhook event {$event->stripe_event_id} queued for retry.",
        ]);
    }
}
