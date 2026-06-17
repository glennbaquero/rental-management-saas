<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('id');
            $table->string('company_email')->nullable()->after('company_name');
            $table->string('company_phone', 30)->nullable()->after('company_email');
            $table->text('address')->nullable()->after('company_phone');
            $table->string('logo')->nullable()->after('address');
            $table->string('timezone', 64)->default('Asia/Manila')->after('logo');
            $table->string('currency', 3)->default('PHP')->after('timezone');
            $table->string('subscription_status', 20)->default('trial')->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'company_name', 'company_email', 'company_phone',
                'address', 'logo', 'timezone', 'currency', 'subscription_status',
            ]);
        });
    }
};
