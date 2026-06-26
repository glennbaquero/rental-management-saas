<?php

declare(strict_types=1);

namespace App\Services\Stripe;

use App\Repositories\StripeRepository;
use Illuminate\Support\Facades\Log;
use Stripe\Customer;

class StripeCustomerService
{
    public function __construct(
        private readonly StripeRepository $stripeRepo,
    ) {}

    public function handleCreated(Customer $customer): void
    {
        $organizationId = $customer->metadata['organization_id'] ?? null;

        if (! $organizationId) {
            Log::channel('stripe')->info('customer.created: no organization_id in metadata', [
                'stripe_customer_id' => $customer->id,
            ]);
            return;
        }

        $tenant = $this->stripeRepo->findTenantById($organizationId);

        if (! $tenant) {
            Log::channel('stripe')->warning('customer.created: tenant not found', [
                'organization_id'    => $organizationId,
                'stripe_customer_id' => $customer->id,
            ]);
            return;
        }

        $tenant->update(['stripe_customer_id' => $customer->id]);

        Log::channel('stripe')->info('customer.created: synced stripe_customer_id', [
            'tenant_id'          => $tenant->id,
            'stripe_customer_id' => $customer->id,
        ]);
    }

    public function handleUpdated(Customer $customer): void
    {
        $tenant = $this->stripeRepo->findTenantByStripeCustomerId($customer->id);

        if (! $tenant) {
            return;
        }

        $updates = [];

        if ($customer->email && $tenant->company_email !== $customer->email) {
            $updates['company_email'] = $customer->email;
        }

        if ($customer->name && $tenant->company_name !== $customer->name) {
            $updates['company_name'] = $customer->name;
        }

        if (! empty($updates)) {
            $tenant->update($updates);
        }

        Log::channel('stripe')->info('customer.updated: synced tenant data', [
            'tenant_id'          => $tenant->id,
            'stripe_customer_id' => $customer->id,
            'updates'            => array_keys($updates),
        ]);
    }

    public function handleDeleted(Customer $customer): void
    {
        $tenant = $this->stripeRepo->findTenantByStripeCustomerId($customer->id);

        if (! $tenant) {
            return;
        }

        $tenant->update(['stripe_customer_id' => null]);

        Log::channel('stripe')->warning('customer.deleted: cleared stripe_customer_id', [
            'tenant_id'          => $tenant->id,
            'stripe_customer_id' => $customer->id,
        ]);
    }
}
