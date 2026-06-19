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
use Database\Seeders\TenantDatabaseSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
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
        $centralDomain = env('CENTRAL_DOMAIN', config('tenancy.central_domains')[0]);
        $fullDomain = ($input['subdomain'] ?? '') . '.' . $centralDomain;

        Validator::make($input, [
            'company_name'     => ['required', 'string', 'max:255'],
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

        try {
            // NOTE: DB::transaction cannot wrap Tenant::create() because TenantCreated fires
            // CreateDatabase (DDL), which causes MySQL to implicitly commit any open transaction.
            // Steps are executed sequentially; on failure we clean up manually.

            $tenant = Tenant::create([
                'company_name'        => $input['company_name'],
                'company_email'       => $input['email'],
                'company_phone'       => $input['company_phone'] ?? null,
                'timezone'            => $input['timezone'],
                'currency'            => $input['currency'],
                'subscription_status' => 'trial',
                'stripe_customer_id'  => $customer->id,
            ]);

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

            tenancy()->initialize($tenant);

            app(TenantDatabaseSeeder::class)->run();

            $ownerRole = Role::where('name', 'owner')->first();

            $user = User::create([
                'name'      => $input['name'],
                'email'     => $input['email'],
                'password'  => $input['password'],
                'role_id'   => $ownerRole?->id,
                'is_active' => true,
            ]);

            Auth::login($user);
            tenancy()->end();
        } catch (\Throwable $e) {
            \Log::error('Tenant creation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if ($tenant) {
                try { $tenant->delete(); } catch (\Throwable) {}
            }
            $this->tryDeleteCustomer($stripe, $customer->id);
            throw ValidationException::withMessages([
                'payment_method_id' => ['Account creation failed. Please try again.'],
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
