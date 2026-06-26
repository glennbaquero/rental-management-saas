<?php

declare(strict_types=1);

use App\Enums\WebhookEventStatus;
use App\Jobs\Stripe\ProcessStripeWebhookJob;
use App\Models\StripeWebhookEvent;
use App\Repositories\WebhookRepository;
use App\Services\Stripe\StripeWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

it('skips processing if the event is already processed', function () {
    $webhookEvent = StripeWebhookEvent::create([
        'stripe_event_id' => 'evt_already_processed',
        'event_type'      => 'customer.created',
        'payload'         => ['id' => 'evt_already_processed', 'type' => 'customer.created', 'object' => 'event', 'data' => ['object' => []]],
        'status'          => WebhookEventStatus::Processed,
        'processed_at'    => now(),
    ]);

    $repo    = app(WebhookRepository::class);
    $service = $this->mock(StripeWebhookService::class, fn (MockInterface $m) =>
        $m->shouldNotReceive('handle')
    );

    $job = new ProcessStripeWebhookJob($webhookEvent->id, $webhookEvent->stripe_event_id);
    $job->handle($repo, $service);

    $webhookEvent->refresh();
    expect($webhookEvent->status)->toBe(WebhookEventStatus::Processed);
});

it('marks the event as failed when an exception is thrown', function () {
    $webhookEvent = StripeWebhookEvent::create([
        'stripe_event_id' => 'evt_will_fail',
        'event_type'      => 'customer.created',
        'payload'         => ['id' => 'evt_will_fail', 'type' => 'customer.created', 'object' => 'event', 'data' => ['object' => []]],
        'status'          => WebhookEventStatus::Pending,
    ]);

    $repo    = app(WebhookRepository::class);
    $service = $this->mock(StripeWebhookService::class, fn (MockInterface $m) =>
        $m->shouldReceive('handle')->once()->andThrow(new \RuntimeException('Stripe API unavailable'))
    );

    $job = new ProcessStripeWebhookJob($webhookEvent->id, $webhookEvent->stripe_event_id);

    try {
        $job->handle($repo, $service);
    } catch (\RuntimeException) {
        // expected
    }

    $job->failed(new \RuntimeException('Stripe API unavailable'));

    $webhookEvent->refresh();
    expect($webhookEvent->status)->toBe(WebhookEventStatus::Failed);
    expect($webhookEvent->error_message)->toBe('Stripe API unavailable');
});

it('increments attempts on each job run', function () {
    $webhookEvent = StripeWebhookEvent::create([
        'stripe_event_id' => 'evt_increment_test',
        'event_type'      => 'customer.created',
        'payload'         => ['id' => 'evt_increment_test', 'type' => 'customer.created', 'object' => 'event', 'data' => ['object' => ['id' => 'cus_test', 'object' => 'customer', 'email' => 'test@test.com', 'metadata' => []]]],
        'status'          => WebhookEventStatus::Pending,
        'attempts'        => 0,
    ]);

    $repo    = app(WebhookRepository::class);
    $service = app(StripeWebhookService::class);

    $job = new ProcessStripeWebhookJob($webhookEvent->id, $webhookEvent->stripe_event_id);
    $job->handle($repo, $service);

    $webhookEvent->refresh();
    expect($webhookEvent->attempts)->toBe(1);
});

it('has the correct queue configuration', function () {
    $job = new ProcessStripeWebhookJob('fake-id', 'evt_fake');

    expect($job->tries)->toBe(3);
    expect($job->backoff)->toBe([30, 60, 120]);
    expect($job->timeout)->toBe(60);
    expect($job->uniqueId())->toBe('evt_fake');
});
