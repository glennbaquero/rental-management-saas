<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_tenants', function (Blueprint $table) {
            $table->string('tenant_code', 20)->nullable()->unique()->after('id');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('gender')->nullable()->after('date_of_birth');
            $table->string('civil_status')->nullable()->after('gender');
            $table->string('alternate_phone', 30)->nullable()->after('phone');
            $table->text('employer_address')->nullable()->after('employer');
            $table->text('current_address')->nullable()->after('employer_address');
            $table->string('city')->nullable()->after('current_address');
            $table->string('province')->nullable()->after('city');
            $table->string('country')->nullable()->after('province');
            $table->string('postal_code', 20)->nullable()->after('country');
            $table->index(['city']);
        });

        DB::statement("ALTER TABLE rental_tenants MODIFY status ENUM('prospect','active','moved_out','blacklisted') DEFAULT 'prospect'");
        DB::statement("ALTER TABLE rental_tenants ADD INDEX idx_rental_tenants_status (status)");
    }

    public function down(): void
    {
        Schema::table('rental_tenants', function (Blueprint $table) {
            $table->dropIndex(['city']);
            $table->dropUnique(['tenant_code']);
            $table->dropColumn([
                'tenant_code',
                'middle_name',
                'gender',
                'civil_status',
                'alternate_phone',
                'employer_address',
                'current_address',
                'city',
                'province',
                'country',
                'postal_code',
            ]);
        });

        DB::statement("ALTER TABLE rental_tenants MODIFY status ENUM('active','inactive','blacklisted') DEFAULT 'active'");
    }
};
