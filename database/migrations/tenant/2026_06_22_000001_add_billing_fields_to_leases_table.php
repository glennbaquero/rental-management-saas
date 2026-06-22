<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'semi_annual', 'annual'])
                ->default('monthly')
                ->after('billing_day');
            $table->unsignedTinyInteger('generate_days_before')
                ->nullable()
                ->after('billing_cycle')
                ->comment('Days before due date to auto-generate invoice; null = generate on due date');
            $table->unsignedTinyInteger('issue_date_offset')
                ->default(0)
                ->after('generate_days_before')
                ->comment('Days before billing_day to set as invoice issue date');
        });
    }

    public function down(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->dropColumn(['billing_cycle', 'generate_days_before', 'issue_date_offset']);
        });
    }
};
