<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\StripePaymentTransaction;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;

class StripeRepository
{
    public function findTenantByStripeCustomerId(string $customerId): ?Tenant
    {
        return Tenant::where('stripe_customer_id', $customerId)->first();
    }

    public function findTenantById(string $tenantId): ?Tenant
    {
        return Tenant::find($tenantId);
    }

    public function findSubscriptionByStripeId(string $stripeSubscriptionId): ?TenantSubscription
    {
        return TenantSubscription::where('stripe_subscription_id', $stripeSubscriptionId)->first();
    }

    public function findPlanByStripePriceId(string $stripePriceId): ?SubscriptionPlan
    {
        return SubscriptionPlan::where('stripe_price_id', $stripePriceId)->first();
    }

    public function upsertSubscription(string $tenantId, array $data): TenantSubscription
    {
        $subscription = TenantSubscription::firstOrNew([
            'stripe_subscription_id' => $data['stripe_subscription_id'],
        ]);

        $subscription->fill(array_merge($data, ['tenant_id' => $tenantId]));
        $subscription->save();

        return $subscription;
    }

    public function createPaymentTransaction(array $data): StripePaymentTransaction
    {
        return StripePaymentTransaction::create($data);
    }

    public function findTransactionByPaymentIntentId(string $paymentIntentId): ?StripePaymentTransaction
    {
        return StripePaymentTransaction::where('stripe_payment_intent_id', $paymentIntentId)->first();
    }

    public function findTransactionByChargeId(string $chargeId): ?StripePaymentTransaction
    {
        return StripePaymentTransaction::where('stripe_charge_id', $chargeId)->first();
    }

    public function updateTenantSubscriptionStatus(Tenant $tenant, string $status): void
    {
        $tenant->update(['subscription_status' => $status]);
    }
}
