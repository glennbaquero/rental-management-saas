<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use App\Enums\SubscriptionStatus;
use App\Repositories\StripeRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\Subscription;

class StripeSubscriptionService
{
    public function __construct(
        private readonly StripeRepository $stripeRepo,
    ) {}

    public function handleCreated(Subscription $subscription): void
    {
        $tenant = $this->stripeRepo->findTenantByStripeCustomerId($subscription->customer);

        if (! $tenant) {
            Log::channel('stripe')->warning('customer.subscription.created: tenant not found', [
                'stripe_customer_id'     => $subscription->customer,
                'stripe_subscription_id' => $subscription->id,
            ]);
            return;
        }

        $firstItem   = $subscription->items->data[0] ?? null;
        $stripePriceId   = $firstItem?->price->id;
        $stripeProductId = $firstItem?->price->product;
        $quantity        = $firstItem?->quantity ?? 1;

        $plan = $stripePriceId ? $this->stripeRepo->findPlanByStripePriceId($stripePriceId) : null;
        $status = SubscriptionStatus::fromStripe($subscription->status);

        $this->stripeRepo->upsertSubscription($tenant->id, [
            'stripe_subscription_id' => $subscription->id,
            'stripe_price_id'        => $stripePriceId,
            'stripe_product_id'      => $stripeProductId,
            'quantity'               => $quantity,
            'plan_id'                => $plan?->id,
            'status'                 => $status->value,
            'current_period_start'   => Carbon::createFromTimestamp($subscription->current_period_start),
            'current_period_end'     => Carbon::createFromTimestamp($subscription->current_period_end),
            'trial_ends_at'          => $subscription->trial_end
                ? Carbon::createFromTimestamp($subscription->trial_end)
                : null,
            'cancel_at'              => $subscription->cancel_at
                ? Carbon::createFromTimestamp($subscription->cancel_at)
                : null,
            'metadata'               => $subscription->metadata->toArray(),
        ]);

        $this->stripeRepo->updateTenantSubscriptionStatus($tenant, $status->value);

        Log::channel('stripe')->info('customer.subscription.created', [
            'tenant_id'              => $tenant->id,
            'stripe_subscription_id' => $subscription->id,
            'status'                 => $status->value,
        ]);
    }

    public function handleUpdated(Subscription $subscription): void
    {
        $existing = $this->stripeRepo->findSubscriptionByStripeId($subscription->id);

        if (! $existing) {
            $this->handleCreated($subscription);
            return;
        }

        $tenant = $this->stripeRepo->findTenantById($existing->tenant_id);

        $firstItem       = $subscription->items->data[0] ?? null;
        $stripePriceId   = $firstItem?->price->id;
        $stripeProductId = $firstItem?->price->product;
        $quantity        = $firstItem?->quantity ?? 1;
        $status          = SubscriptionStatus::fromStripe($subscription->status);

        $plan = $stripePriceId ? $this->stripeRepo->findPlanByStripePriceId($stripePriceId) : null;

        $existing->update([
            'stripe_price_id'      => $stripePriceId,
            'stripe_product_id'    => $stripeProductId,
            'quantity'             => $quantity,
            'plan_id'              => $plan?->id ?? $existing->plan_id,
            'status'               => $status,
            'current_period_start' => Carbon::createFromTimestamp($subscription->current_period_start),
            'current_period_end'   => Carbon::createFromTimestamp($subscription->current_period_end),
            'trial_ends_at'        => $subscription->trial_end
                ? Carbon::createFromTimestamp($subscription->trial_end)
                : null,
            'cancel_at'            => $subscription->cancel_at
                ? Carbon::createFromTimestamp($subscription->cancel_at)
                : null,
            'canceled_at'          => $subscription->canceled_at
                ? Carbon::createFromTimestamp($subscription->canceled_at)
                : null,
            'metadata'             => $subscription->metadata->toArray(),
        ]);

        if ($tenant) {
            $this->stripeRepo->updateTenantSubscriptionStatus($tenant, $status->value);
        }

        Log::channel('stripe')->info('customer.subscription.updated', [
            'tenant_id'              => $existing->tenant_id,
            'stripe_subscription_id' => $subscription->id,
            'status'                 => $status->value,
        ]);
    }

    public function handleDeleted(Subscription $subscription): void
    {
        $existing = $this->stripeRepo->findSubscriptionByStripeId($subscription->id);

        if (! $existing) {
            return;
        }

        $existing->update([
            'status'      => SubscriptionStatus::Canceled,
            'canceled_at' => $subscription->canceled_at
                ? Carbon::createFromTimestamp($subscription->canceled_at)
                : now(),
        ]);

        $tenant = $this->stripeRepo->findTenantById($existing->tenant_id);

        if ($tenant) {
            $this->stripeRepo->updateTenantSubscriptionStatus($tenant, SubscriptionStatus::Canceled->value);
        }

        Log::channel('stripe')->info('customer.subscription.deleted', [
            'tenant_id'              => $existing->tenant_id,
            'stripe_subscription_id' => $subscription->id,
        ]);
    }

    public function handlePaused(Subscription $subscription): void
    {
        $existing = $this->stripeRepo->findSubscriptionByStripeId($subscription->id);

        if (! $existing) {
            return;
        }

        $existing->update([
            'status'    => SubscriptionStatus::Paused,
            'paused_at' => now(),
        ]);

        $tenant = $this->stripeRepo->findTenantById($existing->tenant_id);

        if ($tenant) {
            $this->stripeRepo->updateTenantSubscriptionStatus($tenant, SubscriptionStatus::Paused->value);
        }

        Log::channel('stripe')->info('customer.subscription.paused', [
            'tenant_id'              => $existing->tenant_id,
            'stripe_subscription_id' => $subscription->id,
        ]);
    }

    public function handleResumed(Subscription $subscription): void
    {
        $existing = $this->stripeRepo->findSubscriptionByStripeId($subscription->id);

        if (! $existing) {
            return;
        }

        $existing->update([
            'status'    => SubscriptionStatus::Active,
            'paused_at' => null,
        ]);

        $tenant = $this->stripeRepo->findTenantById($existing->tenant_id);

        if ($tenant) {
            $this->stripeRepo->updateTenantSubscriptionStatus($tenant, SubscriptionStatus::Active->value);
        }

        Log::channel('stripe')->info('customer.subscription.resumed', [
            'tenant_id'              => $existing->tenant_id,
            'stripe_subscription_id' => $subscription->id,
        ]);
    }

    public function handleTrialWillEnd(Subscription $subscription): void
    {
        $existing = $this->stripeRepo->findSubscriptionByStripeId($subscription->id);

        if (! $existing) {
            return;
        }

        Log::channel('stripe')->info('customer.subscription.trial_will_end', [
            'tenant_id'              => $existing->tenant_id,
            'stripe_subscription_id' => $subscription->id,
            'trial_end'              => $subscription->trial_end,
        ]);
    }
}
