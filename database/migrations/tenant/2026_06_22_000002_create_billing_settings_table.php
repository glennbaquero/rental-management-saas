<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('currency', 3)->default('PHP');
            $table->string('timezone', 50)->default('Asia/Manila');
            $table->string('invoice_prefix', 10)->default('INV');
            $table->string('invoice_number_format', 50)->default('{PREFIX}-{YEAR}-{SEQ5}');
            $table->unsignedTinyInteger('grace_period_days')->default(3);
            $table->boolean('auto_generate_invoices')->default(true);
            $table->boolean('auto_send_reminders')->default(true);
            $table->boolean('late_fee_enabled')->default(false);
            $table->enum('late_fee_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('late_fee_amount', 10, 2)->default(0);
            $table->decimal('late_fee_percentage', 5, 2)->default(0);
            $table->unsignedTinyInteger('apply_late_fee_after_days')->default(1);
            $table->boolean('compound_monthly')->default(false);
            $table->json('reminder_days_before')->nullable()->comment('e.g. [7, 3, 1]');
            $table->json('reminder_channels')->nullable()->comment('e.g. ["email","in_app"]');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_settings');
    }
};
