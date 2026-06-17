<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Fortify\CreateTenantWithAdmin;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class TenantRegistrationController extends Controller
{
    public function create(): Response
    {
        $centralDomain = config('tenancy.central_domains')[0];

        return Inertia::render('auth/TenantRegister', [
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'timezones'     => \DateTimeZone::listIdentifiers(),
            'currencies'    => ['USD', 'EUR', 'GBP', 'PHP', 'AUD', 'CAD', 'SGD', 'MYR', 'INR', 'JPY'],
            'centralDomain' => $centralDomain,
        ]);
    }

    public function store(Request $request, CreateTenantWithAdmin $action): RedirectResponse
    {
        [$tenant, $user, $domain] = $action->create($request->all());

        Auth::login($user);

        return redirect()->away('http://' . $domain->domain . '/dashboard');
    }
}
