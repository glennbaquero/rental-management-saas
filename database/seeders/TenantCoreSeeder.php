<?php

namespace Database\Seeders;

use App\Enums\LateFeeType;
use App\Models\BillingSettings;
use App\Models\Role;
use Illuminate\Database\Seeder;

class TenantCoreSeeder extends Seeder
{
    /**
     * Seed the essentials every tenant database needs: the role set and one
     * billing_settings row. Run on signup and by `php artisan db:seed`.
     *
     * @param  array{currency?: string, timezone?: string}  $options
     */
    public function run(array $options = []): void
    {
        $this->seedRoles();
        $this->seedBillingSettings(
            $options['currency'] ?? 'USD',
            $options['timezone'] ?? 'UTC',
        );
    }

    private function seedRoles(): void
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
    }

    private function seedBillingSettings(string $currency, string $timezone): void
    {
        BillingSettings::updateOrCreate([], [
            'currency'                  => strtoupper($currency),
            'timezone'                  => $timezone,
            'invoice_prefix'            => 'INV',
            'invoice_number_format'     => '{PREFIX}-{YEAR}-{SEQ5}',
            'grace_period_days'         => 3,
            'auto_generate_invoices'    => true,
            'auto_send_reminders'       => true,
            'late_fee_enabled'          => false,
            'late_fee_type'             => LateFeeType::Fixed->value,
            'late_fee_amount'           => 0,
            'late_fee_percentage'       => 0,
            'apply_late_fee_after_days' => 1,
            'compound_monthly'          => false,
            'reminder_days_before'      => [7, 3, 1, -1, -7],
            'reminder_channels'         => ['email', 'in_app'],
        ]);
    }
}
