<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Opt-in demo dataset for a tenant database (properties, rental tenants, leases,
 * invoices, payments, late fees). NOT run during registration.
 *
 * Run it against an existing tenant with:
 *   php artisan tenants:run "db:seed --class=Database\Seeders\TenantDemoSeeder --force" --tenants=<tenant-id>
 */
class TenantDemoSeeder extends Seeder
{
    public function run(?User $admin = null): void
    {
        $admin ??= User::query()->oldest()->first();

        if (! $admin) {
            throw new \RuntimeException(
                'TenantDemoSeeder requires a User in the tenant database. '
                .'Run TenantCoreSeeder and create an owner user first.'
            );
        }

        app(PropertyDataSeeder::class)->run();
        app(RentalTenantSeeder::class)->run();
        app(BillingSeeder::class)->run($admin);
    }
}
