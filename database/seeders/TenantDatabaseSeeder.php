<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Full local-dev seed for a tenant database: core essentials + demo dataset.
     * Registration uses TenantCoreSeeder directly and never seeds demo data.
     */
    public function run(): void
    {
        app(TenantCoreSeeder::class)->run();

        $owner = User::query()->oldest()->first() ?? User::create([
            'name'      => 'Demo Owner',
            'email'     => 'owner@demo.test',
            'password'  => 'password',
            'role_id'   => Role::where('name', 'owner')->value('id'),
            'is_active' => true,
        ]);

        app(TenantDemoSeeder::class)->run($owner);
    }
}
