<?php

namespace Database\Seeders;

use App\Enums\BillingCycle;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name'           => 'Starter',
                'slug'           => 'starter-monthly',
                'description'    => 'Perfect for small landlords getting started.',
                'price'          => 9.99,
                'billing_cycle'  => BillingCycle::Monthly,
                'max_properties' => 5,
                'max_units'      => 25,
                'max_users'      => 3,
                'features'       => [
                    'Up to 5 properties',
                    'Up to 25 units',
                    '3 team members',
                    'Rent tracking & invoicing',
                    'Maintenance requests',
                    'Email support',
                ],
            ],
            [
                'name'           => 'Starter',
                'slug'           => 'starter-annual',
                'description'    => 'Perfect for small landlords getting started.',
                'price'          => 99.99,
                'billing_cycle'  => BillingCycle::Annual,
                'max_properties' => 5,
                'max_units'      => 25,
                'max_users'      => 3,
                'features'       => [
                    'Up to 5 properties',
                    'Up to 25 units',
                    '3 team members',
                    'Rent tracking & invoicing',
                    'Maintenance requests',
                    'Email support',
                ],
            ],
            [
                'name'           => 'Professional',
                'slug'           => 'professional-monthly',
                'description'    => 'For growing property management businesses.',
                'price'          => 29.99,
                'billing_cycle'  => BillingCycle::Monthly,
                'max_properties' => 20,
                'max_units'      => 100,
                'max_users'      => 10,
                'features'       => [
                    'Up to 20 properties',
                    'Up to 100 units',
                    '10 team members',
                    'Everything in Starter',
                    'Lease management',
                    'Tenant portal',
                    'Priority support',
                ],
            ],
            [
                'name'           => 'Professional',
                'slug'           => 'professional-annual',
                'description'    => 'For growing property management businesses.',
                'price'          => 299.99,
                'billing_cycle'  => BillingCycle::Annual,
                'max_properties' => 20,
                'max_units'      => 100,
                'max_users'      => 10,
                'features'       => [
                    'Up to 20 properties',
                    'Up to 100 units',
                    '10 team members',
                    'Everything in Starter',
                    'Lease management',
                    'Tenant portal',
                    'Priority support',
                ],
            ],
            [
                'name'           => 'Enterprise',
                'slug'           => 'enterprise-monthly',
                'description'    => 'Unlimited scale for large portfolios.',
                'price'          => 79.99,
                'billing_cycle'  => BillingCycle::Monthly,
                'max_properties' => null,
                'max_units'      => null,
                'max_users'      => null,
                'features'       => [
                    'Unlimited properties',
                    'Unlimited units',
                    'Unlimited team members',
                    'Everything in Professional',
                    'Custom integrations',
                    'Dedicated account manager',
                    'SLA guarantee',
                ],
            ],
            [
                'name'           => 'Enterprise',
                'slug'           => 'enterprise-annual',
                'description'    => 'Unlimited scale for large portfolios.',
                'price'          => 799.99,
                'billing_cycle'  => BillingCycle::Annual,
                'max_properties' => null,
                'max_units'      => null,
                'max_users'      => null,
                'features'       => [
                    'Unlimited properties',
                    'Unlimited units',
                    'Unlimited team members',
                    'Everything in Professional',
                    'Custom integrations',
                    'Dedicated account manager',
                    'SLA guarantee',
                ],
            ],
        ];

        $stripe = $this->makeStripeClient();
        $stripeProducts = [];

        foreach ($plans as $planData) {
            $plan = SubscriptionPlan::firstOrCreate(
                ['slug' => $planData['slug']],
                array_merge($planData, ['is_active' => true])
            );

            if ($stripe === null) {
                continue;
            }

            if ($plan->stripe_price_id) {
                $this->command->line("  Skipping {$plan->slug} — stripe_price_id already set.");
                continue;
            }

            try {
                $tierName = $planData['name'];
                if (! isset($stripeProducts[$tierName])) {
                    $stripeProducts[$tierName] = $stripe->products->create([
                        'name'        => $tierName,
                        'description' => $planData['description'],
                        'metadata'    => ['tier' => strtolower($tierName)],
                    ]);
                    $this->command->line("  Created Stripe product: {$stripeProducts[$tierName]->id} ({$tierName})");
                }

                $product = $stripeProducts[$tierName];
                $interval = $planData['billing_cycle'] === BillingCycle::Monthly ? 'month' : 'year';

                $price = $stripe->prices->create([
                    'product'    => $product->id,
                    'currency'   => 'usd',
                    'unit_amount' => (int) round($planData['price'] * 100),
                    'recurring'  => ['interval' => $interval],
                    'nickname'   => $planData['name'] . ' ' . ucfirst($interval) . 'ly',
                    'metadata'   => ['slug' => $planData['slug']],
                ]);

                $plan->update(['stripe_price_id' => $price->id]);

                $this->command->info("  Synced {$plan->slug} → Stripe price {$price->id}");
            } catch (ApiErrorException $e) {
                $this->command->error("  Stripe error for {$plan->slug}: " . $e->getMessage());
            }
        }
    }

    private function makeStripeClient(): ?StripeClient
    {
        $secret = config('services.stripe.secret');

        if (! $secret || str_starts_with($secret, 'sk_test_') === false && str_starts_with($secret, 'sk_live_') === false) {
            $this->command->warn('STRIPE_SECRET is not configured — skipping Stripe sync.');
            return null;
        }

        return new StripeClient($secret);
    }
}
