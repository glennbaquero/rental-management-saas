<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name'         => 'owner',
                'display_name' => 'Owner',
                'permissions'  => [
                    'organization.view_settings',
                    'organization.manage_settings',
                    'organization.manage_users',
                    'organization.manage_billing',
                    'properties.view',
                    'properties.create',
                    'properties.edit',
                    'properties.delete',
                    'tenants.view',
                    'tenants.create',
                    'tenants.edit',
                    'tenants.delete',
                    'leases.view',
                    'leases.create',
                    'leases.edit',
                    'leases.delete',
                    'invoices.view',
                    'invoices.create',
                    'invoices.edit',
                    'invoices.delete',
                    'payments.view',
                    'payments.create',
                    'payments.edit',
                    'payments.delete',
                    'maintenance.view',
                    'maintenance.create',
                    'maintenance.edit',
                    'maintenance.manage',
                    'reports.view',
                    'reports.view_financial',
                    'billing.view',
                    'billing.create_invoice',
                    'billing.manage_invoice',
                    'billing.record_payment',
                    'billing.verify_payment',
                    'billing.manage_settings',
                ],
            ],
            [
                'name'         => 'property_manager',
                'display_name' => 'Property Manager',
                'permissions'  => [
                    'organization.view_settings',
                    'properties.view',
                    'properties.create',
                    'properties.edit',
                    'properties.delete',
                    'tenants.view',
                    'tenants.create',
                    'tenants.edit',
                    'tenants.delete',
                    'leases.view',
                    'leases.create',
                    'leases.edit',
                    'leases.delete',
                    'maintenance.view',
                    'maintenance.create',
                    'maintenance.edit',
                    'maintenance.manage',
                    'reports.view',
                    'billing.view',
                    'billing.create_invoice',
                    'billing.manage_invoice',
                    'billing.record_payment',
                ],
            ],
            [
                'name'         => 'staff',
                'display_name' => 'Staff',
                'permissions'  => [
                    'tenants.view',
                    'maintenance.view',
                    'maintenance.create',
                    'maintenance.edit',
                    'maintenance.manage',
                    'reports.view',
                ],
            ],
            [
                'name'         => 'accountant',
                'display_name' => 'Accountant',
                'permissions'  => [
                    'invoices.view',
                    'invoices.create',
                    'invoices.edit',
                    'invoices.delete',
                    'payments.view',
                    'payments.create',
                    'payments.edit',
                    'payments.delete',
                    'reports.view_financial',
                    'leases.view',
                    'tenants.view',
                    'billing.view',
                    'billing.create_invoice',
                    'billing.manage_invoice',
                    'billing.record_payment',
                    'billing.verify_payment',
                    'billing.manage_settings',
                ],
            ],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                ['name' => $roleData['name']],
                [
                    'display_name' => $roleData['display_name'],
                    'permissions'  => $roleData['permissions'],
                ]
            );
        }

        app(PropertyDataSeeder::class)->run();
        app(RentalTenantSeeder::class)->run();
        app(BillingSeeder::class)->run();
    }
}
