<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use App\Enums\PaymentTransactionStatus;
use App\Enums\SubscriptionStatus;
use App\Repositories\StripeRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\Invoice;

class StripeInvoiceService
{
    public function __construct(
        private readonly StripeRepository $stripeRepo,
    ) {}

    public function handleCreated(Invoice $invoice): void
    {
        Log::channel('stripe')->info('invoice.created', [
            'stripe_invoice_id' => $invoice->id,
            'customer_id'       => $invoice->customer,
        ]);
    }

    public function handleFinalized(Invoice $invoice): void
    {
        Log::channel('stripe')->info('invoice.finalized', [
            'stripe_invoice_id' => $invoice->id,
            'customer_id'       => $invoice->customer,
            'amount_due'        => $invoice->amount_due,
        ]);
    }

    public function handlePaymentSucceeded(Invoice $invoice): void
    {
        $tenant = $this->resolveTenant($invoice);

        if (! $tenant) {
            Log::channel('stripe')->warning('invoice.payment_succeeded: tenant not found', [
                'stripe_invoice_id' => $invoice->id,
            ]);
            return;
        }

        $this->stripeRepo->createPaymentTransaction([
            'tenant_id'              => $tenant->id,
            'stripe_payment_intent_id' => $invoice->payment_intent,
            'stripe_charge_id'       => $invoice->charge,
            'stripe_invoice_id'      => $invoice->id,
            'stripe_subscription_id' => $invoice->subscription,
            'status'                 => PaymentTransactionStatus::Succeeded->value,
            'currency'               => $invoice->currency,
            'amount'                 => $invoice->amount_paid,
            'amount_refunded'        => 0,
            'paid_at'                => $invoice->status_transitions?->paid_at
                ? Carbon::createFromTimestamp($invoice->status_transitions->paid_at)
                : now(),
        ]);

        if ($invoice->subscription) {
            $subscription = $this->stripeRepo->findSubscriptionByStripeId($invoice->subscription);

            if ($subscription) {
                $subscription->update([
                    'status'               => SubscriptionStatus::Active,
                    'current_period_start' => $invoice->period_start
                        ? Carbon::createFromTimestamp($invoice->period_start)
                        : $subscription->current_period_start,
                    'current_period_end'   => $invoice->period_end
                        ? Carbon::createFromTimestamp($invoice->period_end)
                        : $subscription->current_period_end,
                ]);
            }
        }

        $this->stripeRepo->updateTenantSubscriptionStatus($tenant, SubscriptionStatus::Active->value);

        Log::channel('stripe')->info('invoice.payment_succeeded', [
            'tenant_id'         => $tenant->id,
            'stripe_invoice_id' => $invoice->id,
            'amount_paid'       => $invoice->amount_paid,
        ]);
    }

    public function handlePaymentFailed(Invoice $invoice): void
    {
        $tenant = $this->resolveTenant($invoice);

        if (! $tenant) {
            Log::channel('stripe')->warning('invoice.payment_failed: tenant not found', [
                'stripe_invoice_id' => $invoice->id,
            ]);
            return;
        }

        $this->stripeRepo->createPaymentTransaction([
            'tenant_id'              => $tenant->id,
            'stripe_payment_intent_id' => $invoice->payment_intent,
            'stripe_charge_id'       => $invoice->charge,
            'stripe_invoice_id'      => $invoice->id,
            'stripe_subscription_id' => $invoice->subscription,
            'status'                 => PaymentTransactionStatus::Failed->value,
            'currency'               => $invoice->currency,
            'amount'                 => $invoice->amount_due,
            'amount_refunded'        => 0,
        ]);

        if ($invoice->subscription) {
            $subscription = $this->stripeRepo->findSubscriptionByStripeId($invoice->subscription);
            $subscription?->update(['status' => SubscriptionStatus::PastDue]);
        }

        $this->stripeRepo->updateTenantSubscriptionStatus($tenant, SubscriptionStatus::PastDue->value);

        Log::channel('stripe')->warning('invoice.payment_failed', [
            'tenant_id'         => $tenant->id,
            'stripe_invoice_id' => $invoice->id,
            'amount_due'        => $invoice->amount_due,
        ]);
    }

    public function handleMarkedUncollectible(Invoice $invoice): void
    {
        if (! $invoice->subscription) {
            return;
        }

        $subscription = $this->stripeRepo->findSubscriptionByStripeId($invoice->subscription);
        $subscription?->update(['status' => SubscriptionStatus::Unpaid]);

        $tenant = $subscription ? $this->stripeRepo->findTenantById($subscription->tenant_id) : null;

        if ($tenant) {
            $this->stripeRepo->updateTenantSubscriptionStatus($tenant, SubscriptionStatus::Unpaid->value);
        }

        Log::channel('stripe')->warning('invoice.marked_uncollectible', [
            'stripe_invoice_id'      => $invoice->id,
            'stripe_subscription_id' => $invoice->subscription,
        ]);
    }

    public function handleVoided(Invoice $invoice): void
    {
        Log::channel('stripe')->info('invoice.voided', [
            'stripe_invoice_id' => $invoice->id,
            'customer_id'       => $invoice->customer,
        ]);
    }

    private function resolveTenant(Invoice $invoice): ?\App\Models\Tenant
    {
        if ($invoice->subscription) {
            $subscription = $this->stripeRepo->findSubscriptionByStripeId($invoice->subscription);
            if ($subscription) {
                return $this->stripeRepo->findTenantById($subscription->tenant_id);
            }
        }

        return $this->stripeRepo->findTenantByStripeCustomerId($invoice->customer);
    }
}
