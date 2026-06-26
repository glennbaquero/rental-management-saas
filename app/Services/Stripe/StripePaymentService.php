<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use App\Enums\PaymentTransactionStatus;
use App\Repositories\StripeRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Stripe\Charge;
use Stripe\PaymentIntent;

class StripePaymentService
{
    public function __construct(
        private readonly StripeRepository $stripeRepo,
    ) {}

    public function handlePaymentIntentSucceeded(PaymentIntent $paymentIntent): void
    {
        $existing = $this->stripeRepo->findTransactionByPaymentIntentId($paymentIntent->id);

        if ($existing) {
            $existing->update([
                'status'  => PaymentTransactionStatus::Succeeded,
                'paid_at' => now(),
            ]);
        } else {
            $tenant = $this->stripeRepo->findTenantByStripeCustomerId($paymentIntent->customer ?? '');

            if ($tenant) {
                $this->stripeRepo->createPaymentTransaction([
                    'tenant_id'                => $tenant->id,
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'status'                   => PaymentTransactionStatus::Succeeded->value,
                    'currency'                 => $paymentIntent->currency,
                    'amount'                   => $paymentIntent->amount,
                    'paid_at'                  => now(),
                ]);
            }
        }

        Log::channel('stripe')->info('payment_intent.succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount'            => $paymentIntent->amount,
        ]);
    }

    public function handlePaymentIntentFailed(PaymentIntent $paymentIntent): void
    {
        $existing = $this->stripeRepo->findTransactionByPaymentIntentId($paymentIntent->id);

        if ($existing) {
            $existing->update(['status' => PaymentTransactionStatus::Failed]);
        } else {
            $tenant = $this->stripeRepo->findTenantByStripeCustomerId($paymentIntent->customer ?? '');

            if ($tenant) {
                $this->stripeRepo->createPaymentTransaction([
                    'tenant_id'                => $tenant->id,
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'status'                   => PaymentTransactionStatus::Failed->value,
                    'currency'                 => $paymentIntent->currency,
                    'amount'                   => $paymentIntent->amount,
                ]);
            }
        }

        Log::channel('stripe')->warning('payment_intent.payment_failed', [
            'payment_intent_id'   => $paymentIntent->id,
            'failure_message'     => $paymentIntent->last_payment_error?->message,
        ]);
    }

    public function handlePaymentIntentCanceled(PaymentIntent $paymentIntent): void
    {
        $existing = $this->stripeRepo->findTransactionByPaymentIntentId($paymentIntent->id);

        if ($existing) {
            $existing->update(['status' => PaymentTransactionStatus::Canceled]);
        }

        Log::channel('stripe')->info('payment_intent.canceled', [
            'payment_intent_id' => $paymentIntent->id,
        ]);
    }

    public function handleChargeRefunded(Charge $charge): void
    {
        $existing = $this->stripeRepo->findTransactionByChargeId($charge->id);

        if (! $existing) {
            Log::channel('stripe')->warning('charge.refunded: no matching transaction', [
                'charge_id' => $charge->id,
            ]);
            return;
        }

        $amountRefunded = $charge->amount_refunded ?? 0;
        $isFullRefund   = $amountRefunded >= $charge->amount;

        $existing->update([
            'status'          => $isFullRefund
                ? PaymentTransactionStatus::Refunded
                : $existing->status,
            'amount_refunded' => $amountRefunded,
        ]);

        Log::channel('stripe')->info('charge.refunded', [
            'charge_id'       => $charge->id,
            'amount_refunded' => $amountRefunded,
        ]);
    }
}
