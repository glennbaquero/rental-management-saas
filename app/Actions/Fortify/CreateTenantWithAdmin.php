<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Enums\BillingCycle;
use App\Enums\SubscriptionStatus;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\Role;
use App\Models\User;
use App\Services\StripeService;
use Database\Seeders\TenantCoreSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Database\Models\Domain;
use Stripe\Exception\ApiErrorException;

class CreateTenantWithAdmin
{
    use PasswordValidationRules;

    /** @return array{0: Tenant, 1: User, 2: Domain} */
    public function create(array $input, StripeService $stripe): array
    {
        $centralDomain = config('tenancy.central_domain');
        $fullDomain = ($input['subdomain'] ?? '') . '.' . $centralDomain;

        Validator::make($input, [
            'company_name'     => ['required', 'string', 'max:255'],
            'company_phone'    => ['nullable', 'string', 'max:32'],
            'subdomain'        => ['required', 'string', 'min:3', 'max:63', 'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/'],
            'timezone'         => ['required', 'string', 'timezone:all'],
            'currency'         => ['required', 'string', 'size:3'],
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'string', 'email', 'max:255'],
            'password'         => $this->passwordRules(),
            'plan_id'          => ['required', 'uuid', Rule::exists('subscription_plans', 'id')->where('is_active', true)],
            'payment_method_id' => ['required', 'string', 'starts_with:pm_'],
        ])->validate();

        if (Domain::where('domain', $fullDomain)->exists()) {
            throw ValidationException::withMessages([
                'subdomain' => ['This subdomain is already taken. Please choose another.'],
            ]);
        }

        $plan = SubscriptionPlan::findOrFail($input['plan_id']);

        try {
            $customer = $stripe->createCustomer($input['email'], $input['name']);
        } catch (ApiErrorException $e) {
            throw ValidationException::withMessages([
                'payment_method_id' => ['Payment setup failed: ' . $e->getMessage()],
            ]);
        }

        try {
            $stripe->attachPaymentMethod($input['payment_method_id'], $customer->id);
        } catch (ApiErrorException $e) {
            $this->tryDeleteCustomer($stripe, $customer->id);
            throw ValidationException::withMessages([
                'payment_method_id' => ['Could not attach payment method: ' . $e->getMessage()],
            ]);
        }

        $stripeSubscriptionId = null;
        $periodStart = now();
        $periodEnd = $plan->billing_cycle === BillingCycle::Annual
            ? now()->addYear()
            : now()->addMonth();

        if ($plan->stripe_price_id) {
            try {
                $subscription = $stripe->createSubscription(
                    $customer->id,
                    $plan->stripe_price_id,
                    $input['payment_method_id']
                );
                $stripeSubscriptionId = $subscription->id;
                $periodStart = $subscription->current_period_start
                    ? Carbon::createFromTimestamp($subscription->current_period_start)
                    : now();
                $periodEnd = $subscription->current_period_end
                    ? Carbon::createFromTimestamp($subscription->current_period_end)
                    : ($plan->billing_cycle === BillingCycle::Annual ? now()->addYear() : now()->addMonth());
                $trialEndsAt = $subscription->trial_end
                    ? Carbon::createFromTimestamp($subscription->trial_end)
                    : now()->addDays(28);
            } catch (ApiErrorException $e) {
                $this->tryDeleteCustomer($stripe, $customer->id);
                throw ValidationException::withMessages([
                    'payment_method_id' => [$e->getMessage()],
                ]);
            }
        }

        $tenant = $domain = $user = null;
        $centralConn = config('tenancy.database.central_connection');

        try {
            // Creating the tenant synchronously CREATEs + MIGRATEs the tenant database
            // via the TenantCreated job pipeline. DDL implicit-commits on MySQL, so this
            // step cannot participate in a transaction; it is undone by $tenant->delete().
            $tenant = Tenant::create([
                'company_name'        => $input['company_name'],
                'company_email'       => $input['email'],
                'company_phone'       => $input['company_phone'] ?? null,
                'timezone'            => $input['timezone'],
                'currency'            => strtoupper($input['currency']),
                'subscription_status' => 'trial',
                'stripe_customer_id'  => $customer->id,
            ]);

            // Central-side DML only — safe to roll back as a unit.
            DB::connection($centralConn)->transaction(function () use (
                $tenant, $plan, $input, $fullDomain, $stripeSubscriptionId, $periodStart, $periodEnd, $trialEndsAt, &$domain
            ) {
                $domain = $tenant->createDomain($fullDomain);

                TenantSubscription::create([
                    'tenant_id'                => $tenant->id,
                    'plan_id'                  => $plan->id,
                    'status'                   => SubscriptionStatus::Trial,
                    'stripe_subscription_id'   => $stripeSubscriptionId,
                    'stripe_payment_method_id' => $input['payment_method_id'],
                    'trial_ends_at'            => $trialEndsAt ?? now()->addDays(28),
                    'current_period_start'     => $periodStart,
                    'current_period_end'       => $periodEnd,
                ]);
            });

            tenancy()->initialize($tenant);

            // Seed the essentials and create the owner in one tenant-side transaction so
            // a mid-seed failure leaves a clean, empty (but migrated) tenant database.
            DB::connection('tenant')->transaction(function () use ($input, &$user) {
                app(TenantCoreSeeder::class)->run([
                    'currency' => $input['currency'],
                    'timezone' => $input['timezone'],
                ]);

                $ownerRole = Role::where('name', 'owner')->firstOrFail();

                $user = User::create([
                    'name'      => $input['name'],
                    'email'     => $input['email'],
                    'password'  => $input['password'],
                    'role_id'   => $ownerRole->id,
                    'is_active' => true,
                ]);
            });

            Auth::login($user);
            tenancy()->end();
        } catch (\Throwable $e) {
            if (tenancy()->initialized) {
                try { tenancy()->end(); } catch (\Throwable) {}
            }

            report($e);
            \Log::error('Tenant registration failed', [
                'email'     => $input['email'] ?? null,
                'subdomain' => $input['subdomain'] ?? null,
                'exception' => $e::class,
                'message'   => $e->getMessage(),
            ]);

            if ($tenant) {
                try {
                    $tenant->delete();
                } catch (\Throwable $inner) {
                    \Log::error('Tenant rollback failed', [
                        'tenant_id' => $tenant->id,
                        'message'   => $inner->getMessage(),
                    ]);
                }
            }
            $this->tryDeleteCustomer($stripe, $customer->id);

            throw ValidationException::withMessages([
                'payment_method_id' => [
                    config('app.debug')
                        ? 'Account creation failed: ' . $e->getMessage()
                        : 'Account creation failed. Please try again.',
                ],
            ]);
        }

        return [$tenant, $user, $domain];
    }

    private function tryDeleteCustomer(StripeService $stripe, string $customerId): void
    {
        try {
            $stripe->deleteCustomer($customerId);
        } catch (\Throwable) {
            // Best-effort cleanup; log via Laravel's default handler on next request
        }
    }
}
