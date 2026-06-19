<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationSettingsController extends Controller
{
    public function edit(): Response
    {
        $tenant = tenancy()->tenant;

        $subscription = TenantSubscription::with('plan')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->first();

        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price')
            ->get(['id', 'name', 'slug', 'price', 'billing_cycle', 'max_users', 'features']);

        return Inertia::render('organization/Settings', [
            'organization' => [
                'company_name'  => $tenant->company_name,
                'company_email' => $tenant->company_email,
                'company_phone' => $tenant->company_phone,
                'address'       => $tenant->address,
                'tax_id'        => $tenant->tax_id,
                'logo'          => $tenant->logo ? Storage::disk('public')->url($tenant->logo) : null,
                'timezone'      => $tenant->timezone,
                'currency'      => $tenant->currency,
                'status'        => $tenant->subscription_status,
            ],
            'subscription' => $subscription ? [
                'plan_name'     => $subscription->plan->name,
                'billing_cycle' => $subscription->plan->billing_cycle->value,
                'status'        => $subscription->status->value,
                'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                'period_end'    => $subscription->current_period_end?->toIso8601String(),
                'price'         => $subscription->plan->price,
            ] : null,
            'plans' => $plans,
        ]);
    }

    public function update(UpdateOrganizationRequest $request): RedirectResponse
    {
        $tenant = tenancy()->tenant;
        $tenant->update($request->validated());

        return back()->with('toast', ['type' => 'success', 'message' => 'Organization settings updated.']);
    }

    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp,svg'],
        ]);

        $tenant = tenancy()->tenant;

        if ($tenant->logo) {
            Storage::disk('public')->delete($tenant->logo);
        }

        $path = $request->file('logo')->store(
            'logos/' . $tenant->id,
            'public'
        );

        $tenant->update(['logo' => $path]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Logo updated successfully.']);
    }
}
