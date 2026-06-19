<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\CreateTenantWithAdmin;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Stripe\Exception\ApiErrorException;

class TenantRegistrationController extends Controller
{
    public function create(): InertiaResponse
    {
        $centralDomain = env('CENTRAL_DOMAIN', config('tenancy.central_domains')[0]);

        return Inertia::render('auth/TenantRegister', [
            'passwordRules'      => Password::defaults()->toPasswordRulesString(),
            'timezones'          => \DateTimeZone::listIdentifiers(),
            'currencies'         => ['USD', 'EUR', 'GBP', 'PHP', 'AUD', 'CAD', 'SGD', 'MYR', 'INR', 'JPY'],
            'centralDomain'      => $centralDomain,
            'stripeKey'          => config('services.stripe.key'),
            'subscriptionPlans'  => SubscriptionPlan::where('is_active', true)
                ->orderBy('price')
                ->get()
                ->map(fn (SubscriptionPlan $p) => [
                    'id'             => $p->id,
                    'name'           => $p->name,
                    'slug'           => $p->slug,
                    'description'    => $p->description,
                    'price'          => (float) $p->price,
                    'billing_cycle'  => $p->billing_cycle->value,
                    'max_properties' => $p->max_properties,
                    'max_units'      => $p->max_units,
                    'max_users'      => $p->max_users,
                    'features'       => $p->features,
                    'stripe_price_id' => $p->stripe_price_id,
                ]),
        ]);
    }

    public function store(
        Request $request,
        CreateTenantWithAdmin $action,
        StripeService $stripe
    ): Response {
        try {
            [$tenant, $user, $domain] = $action->create($request->all(), $stripe);
        } catch (ValidationException $e) {
            throw $e;
        } catch (ApiErrorException $e) {
            throw ValidationException::withMessages([
                'payment_method_id' => [$e->getMessage()],
            ]);
        }

        $scheme = app()->environment('production') ? 'https' : 'http';

        return Inertia::location("{$scheme}://{$domain->domain}/dashboard");
    }
}
