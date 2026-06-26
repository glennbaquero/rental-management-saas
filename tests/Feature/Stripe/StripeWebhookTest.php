<?php

declare(strict_types=1);

use App\Enums\SubscriptionStatus;
use App\Enums\WebhookEventStatus;
use App\Jobs\Stripe\ProcessStripeWebhookJob;
use App\Models\StripePaymentTransaction;
use App\Models\StripeWebhookEvent;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Support\Facades\Queue;
use Stripe\Event;
use Stripe\Webhook;

beforeEach(function () {
    Queue::fake();
});

function makeStripeSignature(string $payload, string $secret): string
{
    $timestamp = time();
    $sig       = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

    return "t={$timestamp},v1={$sig}";
}

function makeWebhookPayload(string $type, array $object = []): array
{
    return [
        'id'      => 'evt_test_' . uniqid(),
        'object'  => 'event',
        'type'    => $type,
        'livemode' => false,
        'created' => time(),
        'data'    => ['object' => $object],
    ];
}

it('accepts a valid webhook and queues the job', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test_secret']);

    $payload = json_encode(makeWebhookPayload('customer.created', [
        'id'       => 'cus_test',
        'object'   => 'customer',
        'email'    => 'test@example.com',
        'metadata' => ['organization_id' => 'org_123'],
    ]));

    $sig = makeStripeSignature($payload, 'whsec_test_secret');

    $this->withoutMiddleware(\App\Http\Middleware\InitializeTenancyBySubdomain::class)
        ->postJson('/stripe/webhook', json_decode($payload, true), [
            'Stripe-Signature' => $sig,
            'Content-Type'     => 'application/json',
        ])
        ->assertOk()
        ->assertJson(['received' => true]);

    $this->assertDatabaseHas('stripe_webhook_events', [
        'event_type' => 'customer.created',
        'status'     => WebhookEventStatus::Pending->value,
    ]);

    Queue::assertPushed(ProcessStripeWebhookJob::class);
});

it('returns 400 for an invalid webhook signature', function () {
    config(['services.stripe.webhook_secret' => 'whsec_real_secret']);

    $payload = json_encode(makeWebhookPayload('customer.created'));

    $this->withoutMiddleware(\App\Http\Middleware\InitializeTenancyBySubdomain::class)
        ->postJson('/stripe/webhook', json_decode($payload, true), [
            'Stripe-Signature' => 't=1,v1=badsignature',
            'Content-Type'     => 'application/json',
        ])
        ->assertStatus(400)
        ->assertJson(['error' => 'Invalid signature.']);

    Queue::assertNotPushed(ProcessStripeWebhookJob::class);
});

it('ignores duplicate webhook events and does not re-queue', function () {
    config(['services.stripe.webhook_secret' => 'whsec_test_secret']);

    $stripeEventId = 'evt_duplicate_test';

    StripeWebhookEvent::create([
        'stripe_event_id' => $stripeEventId,
        'event_type'      => 'customer.updated',
        'payload'         => ['id' => $stripeEventId, 'type' => 'customer.updated', 'data' => ['object' => []]],
        'status'          => WebhookEventStatus::Processed,
        'processed_at'    => now(),
    ]);

    $payload = json_encode([
        'id'      => $stripeEventId,
        'object'  => 'event',
        'type'    => 'customer.updated',
        'livemode' => false,
        'created' => time(),
        'data'    => ['object' => ['id' => 'cus_test', 'object' => 'customer', 'metadata' => []]],
    ]);

    $sig = makeStripeSignature($payload, 'whsec_test_secret');

    $this->withoutMiddleware(\App\Http\Middleware\InitializeTenancyBySubdomain::class)
        ->postJson('/stripe/webhook', json_decode($payload, true), [
            'Stripe-Signature' => $sig,
            'Content-Type'     => 'application/json',
        ])
        ->assertOk()
        ->assertJson(['received' => true]);

    Queue::assertNotPushed(ProcessStripeWebhookJob::class);
});

it('marks the subscription as active on checkout.session.completed', function () {
    $tenant = Tenant::factory()->create([
        'stripe_customer_id' => 'cus_checkout_test',
        'subscription_status' => 'trial',
    ]);

    $plan = \App\Models\SubscriptionPlan::factory()->create([
        'stripe_price_id' => 'price_test_monthly',
    ]);

    Queue::fake();

    $webhookEvent = StripeWebhookEvent::create([
        'stripe_event_id' => 'evt_checkout_test',
        'event_type'      => 'checkout.session.completed',
        'tenant_id'       => $tenant->id,
        'payload'         => [
            'id'     => 'evt_checkout_test',
            'type'   => 'checkout.session.completed',
            'object' => 'event',
            'data'   => [
                'object' => [
                    'id'           => 'cs_test',
                    'object'       => 'checkout.session',
                    'mode'         => 'subscription',
                    'customer'     => 'cus_checkout_test',
                    'subscription' => 'sub_test_001',
                    'metadata'     => [
                        'organization_id' => $tenant->id,
                        'stripe_price_id' => 'price_test_monthly',
                    ],
                ],
            ],
        ],
        'status' => WebhookEventStatus::Pending,
    ]);

    $job = new ProcessStripeWebhookJob($webhookEvent->id, $webhookEvent->stripe_event_id);
    $job->handle(
        app(\App\Repositories\WebhookRepository::class),
        app(\App\Services\Stripe\StripeWebhookService::class),
    );

    $tenant->refresh();
    expect($tenant->subscription_status)->toBe(SubscriptionStatus::Active->value);

    $this->assertDatabaseHas('tenant_subscriptions', [
        'tenant_id'              => $tenant->id,
        'stripe_subscription_id' => 'sub_test_001',
        'status'                 => SubscriptionStatus::Active->value,
    ]);
});

it('marks subscription as past_due on invoice.payment_failed', function () {
    $tenant = Tenant::factory()->create([
        'stripe_customer_id' => 'cus_invoice_test',
        'subscription_status' => 'active',
    ]);

    $subscription = TenantSubscription::factory()->create([
        'tenant_id'              => $tenant->id,
        'stripe_subscription_id' => 'sub_invoice_test',
        'status'                 => SubscriptionStatus::Active,
    ]);

    $webhookEvent = StripeWebhookEvent::create([
        'stripe_event_id' => 'evt_invoice_failed',
        'event_type'      => 'invoice.payment_failed',
        'tenant_id'       => $tenant->id,
        'payload'         => [
            'id'     => 'evt_invoice_failed',
            'type'   => 'invoice.payment_failed',
            'object' => 'event',
            'data'   => [
                'object' => [
                    'id'           => 'in_test',
                    'object'       => 'invoice',
                    'customer'     => 'cus_invoice_test',
                    'subscription' => 'sub_invoice_test',
                    'amount_due'   => 2900,
                    'currency'     => 'usd',
                    'payment_intent' => null,
                    'charge'         => null,
                ],
            ],
        ],
        'status' => WebhookEventStatus::Pending,
    ]);

    $job = new ProcessStripeWebhookJob($webhookEvent->id, $webhookEvent->stripe_event_id);
    $job->handle(
        app(\App\Repositories\WebhookRepository::class),
        app(\App\Services\Stripe\StripeWebhookService::class),
    );

    $subscription->refresh();
    expect($subscription->status)->toBe(SubscriptionStatus::PastDue);

    $tenant->refresh();
    expect($tenant->subscription_status)->toBe(SubscriptionStatus::PastDue->value);
});

it('creates a payment transaction on invoice.payment_succeeded', function () {
    $tenant = Tenant::factory()->create([
        'stripe_customer_id'  => 'cus_inv_success',
        'subscription_status' => 'active',
    ]);

    $subscription = TenantSubscription::factory()->create([
        'tenant_id'              => $tenant->id,
        'stripe_subscription_id' => 'sub_inv_success',
        'status'                 => SubscriptionStatus::Active,
    ]);

    $webhookEvent = StripeWebhookEvent::create([
        'stripe_event_id' => 'evt_inv_success',
        'event_type'      => 'invoice.payment_succeeded',
        'tenant_id'       => $tenant->id,
        'payload'         => [
            'id'     => 'evt_inv_success',
            'type'   => 'invoice.payment_succeeded',
            'object' => 'event',
            'data'   => [
                'object' => [
                    'id'             => 'in_success',
                    'object'         => 'invoice',
                    'customer'       => 'cus_inv_success',
                    'subscription'   => 'sub_inv_success',
                    'amount_paid'    => 2900,
                    'currency'       => 'usd',
                    'payment_intent' => 'pi_test_001',
                    'charge'         => 'ch_test_001',
                    'period_start'   => time(),
                    'period_end'     => time() + 2592000,
                    'status_transitions' => ['paid_at' => time()],
                ],
            ],
        ],
        'status' => WebhookEventStatus::Pending,
    ]);

    $job = new ProcessStripeWebhookJob($webhookEvent->id, $webhookEvent->stripe_event_id);
    $job->handle(
        app(\App\Repositories\WebhookRepository::class),
        app(\App\Services\Stripe\StripeWebhookService::class),
    );

    $this->assertDatabaseHas('stripe_payment_transactions', [
        'tenant_id'              => $tenant->id,
        'stripe_invoice_id'      => 'in_success',
        'status'                 => 'succeeded',
        'amount'                 => 2900,
    ]);
});

it('marks event as processed in the database after successful job run', function () {
    $tenant = Tenant::factory()->create(['stripe_customer_id' => 'cus_basic_test']);

    $webhookEvent = StripeWebhookEvent::create([
        'stripe_event_id' => 'evt_basic_test',
        'event_type'      => 'customer.created',
        'tenant_id'       => $tenant->id,
        'payload'         => [
            'id'     => 'evt_basic_test',
            'type'   => 'customer.created',
            'object' => 'event',
            'data'   => [
                'object' => [
                    'id'       => 'cus_basic_test',
                    'object'   => 'customer',
                    'email'    => 'org@example.com',
                    'metadata' => ['organization_id' => $tenant->id],
                ],
            ],
        ],
        'status' => WebhookEventStatus::Pending,
    ]);

    $job = new ProcessStripeWebhookJob($webhookEvent->id, $webhookEvent->stripe_event_id);
    $job->handle(
        app(\App\Repositories\WebhookRepository::class),
        app(\App\Services\Stripe\StripeWebhookService::class),
    );

    $webhookEvent->refresh();

    expect($webhookEvent->status)->toBe(WebhookEventStatus::Processed);
    expect($webhookEvent->processed_at)->not->toBeNull();
});
