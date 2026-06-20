<?php

namespace Database\Seeders;

use App\Models\EmergencyContact;
use App\Models\RentalTenant;
use App\Models\TenantIdDocument;
use Illuminate\Database\Seeder;

class RentalTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            [
                'tenant' => [
                    'tenant_code'    => 'TC-0001',
                    'first_name'     => 'Juan Miguel',
                    'middle_name'    => 'Santos',
                    'last_name'      => 'dela Cruz',
                    'email'          => 'juan.delacruz@email.com',
                    'phone'          => '0917-123-4567',
                    'alternate_phone' => '0932-765-4321',
                    'date_of_birth'  => '1990-03-15',
                    'gender'         => 'male',
                    'civil_status'   => 'single',
                    'nationality'    => 'Filipino',
                    'current_address' => '45 Mabini Street',
                    'city'           => 'Quezon City',
                    'province'       => 'Metro Manila',
                    'country'        => 'Philippines',
                    'postal_code'    => '1100',
                    'occupation'     => 'Software Engineer',
                    'employer'       => 'TechCorp Philippines Inc.',
                    'employer_address' => '8th Floor, One Bonifacio High Street, BGC, Taguig City',
                    'monthly_income' => 75000.00,
                    'status'         => 'active',
                    'notes'          => 'Reliable tenant. Never missed a payment. Has a quiet lifestyle.',
                ],
                'documents' => [
                    [
                        'type'                => 'national_id',
                        'document_number'     => 'PSN-1234-5678-9012',
                        'issued_by'           => 'Philippine Statistics Authority',
                        'issued_date'         => '2022-01-10',
                        'expiry_date'         => '2032-01-10',
                        'verification_status' => 'verified',
                    ],
                    [
                        'type'                => 'passport',
                        'document_number'     => 'P1234567A',
                        'issued_by'           => 'Department of Foreign Affairs',
                        'issued_date'         => '2021-06-20',
                        'expiry_date'         => '2031-06-20',
                        'verification_status' => 'verified',
                    ],
                ],
                'contacts' => [
                    [
                        'name'             => 'Elena Santos dela Cruz',
                        'relationship'     => 'Mother',
                        'phone'            => '0919-987-6543',
                        'alternate_number' => '044-123-4567',
                        'email'            => 'elena.delacruz@email.com',
                        'address'          => '12 Sampaguita St, San Jose, Bulacan',
                        'is_primary'       => true,
                    ],
                    [
                        'name'             => 'Roberto dela Cruz Jr.',
                        'relationship'     => 'Father',
                        'phone'            => '0906-111-2222',
                        'alternate_number' => null,
                        'email'            => null,
                        'address'          => '12 Sampaguita St, San Jose, Bulacan',
                        'is_primary'       => false,
                    ],
                ],
            ],
            [
                'tenant' => [
                    'tenant_code'    => 'TC-0002',
                    'first_name'     => 'Maria Cristina',
                    'middle_name'    => 'Reyes',
                    'last_name'      => 'Santos',
                    'email'          => 'maria.santos@email.com',
                    'phone'          => '0932-987-6543',
                    'alternate_phone' => null,
                    'date_of_birth'  => '1995-07-22',
                    'gender'         => 'female',
                    'civil_status'   => 'single',
                    'nationality'    => 'Filipino',
                    'current_address' => '78 Rizal Avenue, Unit 3B',
                    'city'           => 'Manila',
                    'province'       => 'Metro Manila',
                    'country'        => 'Philippines',
                    'postal_code'    => '1000',
                    'occupation'     => 'Marketing Manager',
                    'employer'       => 'Global Brands Philippines',
                    'employer_address' => '30th Floor, PBCom Tower, Ayala Ave, Makati City',
                    'monthly_income' => 55000.00,
                    'status'         => 'prospect',
                    'notes'          => 'Inquired about 2-bedroom units. Currently looking to move in next month.',
                ],
                'documents' => [
                    [
                        'type'                => 'drivers_license',
                        'document_number'     => 'N01-23-456789',
                        'issued_by'           => 'Land Transportation Office',
                        'issued_date'         => '2020-08-15',
                        'expiry_date'         => '2025-08-15',
                        'verification_status' => 'pending',
                    ],
                ],
                'contacts' => [
                    [
                        'name'             => 'Josefina Reyes Santos',
                        'relationship'     => 'Mother',
                        'phone'            => '0917-555-8888',
                        'alternate_number' => null,
                        'email'            => 'josefina.santos@email.com',
                        'address'          => '22 Kamuning Road, Quezon City',
                        'is_primary'       => true,
                    ],
                ],
            ],
            [
                'tenant' => [
                    'tenant_code'    => 'TC-0003',
                    'first_name'     => 'Roberto',
                    'middle_name'    => 'Garcia',
                    'last_name'      => 'Reyes',
                    'email'          => 'roberto.reyes@email.com',
                    'phone'          => '0919-555-1234',
                    'alternate_phone' => '0928-000-9999',
                    'date_of_birth'  => '1985-11-30',
                    'gender'         => 'male',
                    'civil_status'   => 'married',
                    'nationality'    => 'Filipino',
                    'current_address' => '101 Batangas Street, Brgy. Pembo',
                    'city'           => 'Makati City',
                    'province'       => 'Metro Manila',
                    'country'        => 'Philippines',
                    'postal_code'    => '1218',
                    'occupation'     => 'Business Owner',
                    'employer'       => 'Reyes Trading Co.',
                    'employer_address' => '456 Libertad Street, Mandaluyong City',
                    'monthly_income' => 120000.00,
                    'status'         => 'moved_out',
                    'notes'          => 'Moved out Dec 2025 after lease expiry. Left unit in excellent condition. Highly recommended as a tenant.',
                ],
                'documents' => [
                    [
                        'type'                => 'passport',
                        'document_number'     => 'P9876543B',
                        'issued_by'           => 'Department of Foreign Affairs',
                        'issued_date'         => '2019-04-01',
                        'expiry_date'         => '2029-04-01',
                        'verification_status' => 'verified',
                    ],
                    [
                        'type'                => 'sss',
                        'document_number'     => '33-1234567-8',
                        'issued_by'           => 'Social Security System',
                        'issued_date'         => '2005-01-01',
                        'expiry_date'         => null,
                        'verification_status' => 'verified',
                    ],
                ],
                'contacts' => [
                    [
                        'name'             => 'Carmela Santos Reyes',
                        'relationship'     => 'Spouse',
                        'phone'            => '0917-777-3333',
                        'alternate_number' => '02-8123-4567',
                        'email'            => 'carmela.reyes@email.com',
                        'address'          => '101 Batangas Street, Brgy. Pembo, Makati City',
                        'is_primary'       => true,
                    ],
                ],
            ],
        ];

        foreach ($tenants as $data) {
            $tenant = RentalTenant::create($data['tenant']);

            foreach ($data['documents'] as $doc) {
                TenantIdDocument::create(array_merge($doc, ['rental_tenant_id' => $tenant->id]));
            }

            foreach ($data['contacts'] as $contact) {
                EmergencyContact::create(array_merge($contact, ['rental_tenant_id' => $tenant->id]));
            }
        }
    }
}
