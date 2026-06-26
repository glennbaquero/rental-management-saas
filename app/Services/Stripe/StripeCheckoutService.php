<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use App\Enums\SubscriptionStatus;
use App\Repositories\StripeRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;

class StripeCheckoutService
{
    public function __construct(
        private readonly StripeRepository $stripeRepo,
    ) {}

    public function handleCompleted(Session $session): void
    {
        if ($session->mode !== 'subscription') {
            return;
        }

        $organizationId = $session->metadata['organization_id'] ?? null;

        if (! $organizationId) {
            Log::channel('stripe')->warning('checkout.session.completed: missing organization_id', [
                'session_id' => $session->id,
            ]);
            return;
        }

        $tenant = $this->stripeRepo->findTenantById($organizationId);

        if (! $tenant) {
            Log::channel('stripe')->error('checkout.session.completed: tenant not found', [
                'organization_id' => $organizationId,
                'session_id'      => $session->id,
            ]);
            return;
        }

        if ($tenant->stripe_customer_id !== $session->customer) {
            $tenant->update(['stripe_customer_id' => $session->customer]);
        }

        $plan = $this->stripeRepo->findPlanByStripePriceId($session->metadata['stripe_price_id'] ?? '');

        $this->stripeRepo->upsertSubscription($tenant->id, [
            'stripe_subscription_id' => $session->subscription,
            'stripe_price_id'        => $session->metadata['stripe_price_id'] ?? null,
            'plan_id'                => $plan?->id,
            'status'                 => SubscriptionStatus::Active->value,
            'current_period_start'   => now(),
        ]);

        $this->stripeRepo->updateTenantSubscriptionStatus($tenant, SubscriptionStatus::Active->value);

        Log::channel('stripe')->info('checkout.session.completed: subscription activated', [
            'tenant_id'   => $tenant->id,
            'session_id'  => $session->id,
            'customer_id' => $session->customer,
        ]);
    }

    public function handleAsyncPaymentSucceeded(Session $session): void
    {
        if ($session->mode !== 'subscription') {
            return;
        }

        $this->handleCompleted($session);
    }

    public function handleAsyncPaymentFailed(Session $session): void
    {
        $organizationId = $session->metadata['organization_id'] ?? null;

        if (! $organizationId) {
            return;
        }

        $tenant = $this->stripeRepo->findTenantById($organizationId);

        if (! $tenant) {
            return;
        }

        $subscription = $this->stripeRepo->findSubscriptionByStripeId($session->subscription ?? '');

        if ($subscription) {
            $subscription->update(['status' => SubscriptionStatus::Incomplete]);
        }

        $this->stripeRepo->updateTenantSubscriptionStatus($tenant, SubscriptionStatus::Incomplete->value);

        Log::channel('stripe')->warning('checkout.session.async_payment_failed', [
            'tenant_id'  => $tenant->id,
            'session_id' => $session->id,
        ]);
    }
}
