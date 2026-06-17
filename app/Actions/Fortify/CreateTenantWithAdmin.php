<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Stancl\Tenancy\Database\Models\Domain;

class CreateTenantWithAdmin
{
    use PasswordValidationRules;

    /**
     * Validate input, create a new Tenant + Domain + admin User.
     *
     * @return array{0: Tenant, 1: User, 2: Domain}
     */
    public function create(array $input): array
    {
        $centralDomain = config('tenancy.central_domains')[0];
        $fullDomain = ($input['subdomain'] ?? '') . '.' . $centralDomain;

        Validator::make($input, [
            'company_name' => ['required', 'string', 'max:255'],
            'subdomain'    => ['required', 'string', 'min:3', 'max:63', 'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/'],
            'timezone'     => ['required', 'string', 'timezone:all'],
            'currency'     => ['required', 'string', 'size:3'],
            'name'         => ['required', 'string', 'max:255'],
            'email'        => ['required', 'string', 'email', 'max:255'],
            'password'     => $this->passwordRules(),
        ])->validate();

        if (Domain::where('domain', $fullDomain)->exists()) {
            throw ValidationException::withMessages([
                'subdomain' => ['This subdomain is already taken. Please choose another.'],
            ]);
        }

        $tenant = Tenant::create([
            'company_name'        => $input['company_name'],
            'company_email'       => $input['email'],
            'company_phone'       => $input['company_phone'] ?? null,
            'timezone'            => $input['timezone'],
            'currency'            => $input['currency'],
            'subscription_status' => 'trial',
        ]);

        $domain = $tenant->createDomain($fullDomain);

        tenancy()->initialize($tenant);

        $user = User::create([
            'name'      => $input['name'],
            'email'     => $input['email'],
            'password'  => $input['password'],
            'is_active' => true,
        ]);

        return [$tenant, $user, $domain];
    }
}
