<?php

namespace App\Services;

use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentMethod;
use Stripe\Subscription;
use Stripe\StripeClient;

class StripeService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * @throws ApiErrorException
     */
    public function createCustomer(string $email, string $name): Customer
    {
        return $this->stripe->customers->create([
            'email' => $email,
            'name'  => $name,
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function attachPaymentMethod(string $paymentMethodId, string $customerId): void
    {
        $this->stripe->paymentMethods->attach($paymentMethodId, ['customer' => $customerId]);

        $this->stripe->customers->update($customerId, [
            'invoice_settings' => ['default_payment_method' => $paymentMethodId],
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function createSubscription(string $customerId, string $stripePriceId, string $paymentMethodId): Subscription
    {
        return $this->stripe->subscriptions->create([
            'customer'               => $customerId,
            'items'                  => [['price' => $stripePriceId]],
            'default_payment_method' => $paymentMethodId,
            'trial_period_days'      => 28,
            'payment_behavior'       => 'error_if_incomplete',
            'expand'                 => ['latest_invoice.payment_intent'],
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function deleteCustomer(string $customerId): void
    {
        $this->stripe->customers->delete($customerId);
    }
}
