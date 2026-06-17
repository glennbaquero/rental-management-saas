<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('unit_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('rental_tenant_id')->constrained('rental_tenants')->restrictOnDelete();
            $table->string('lease_number')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('rent_amount', 10, 2);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->boolean('deposit_paid')->default(false);
            $table->date('deposit_paid_date')->nullable();
            $table->unsignedTinyInteger('billing_day')->default(1)->comment('Day of month to generate invoice (1-28)');
            $table->enum('status', ['pending', 'active', 'expired', 'terminated'])->default('pending');
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lease_co_tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lease_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('rental_tenant_id')->constrained('rental_tenants')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['lease_id', 'rental_tenant_id']);
        });

        Schema::create('lease_renewals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lease_id')->constrained()->cascadeOnDelete();
            $table->date('previous_end_date');
            $table->date('new_end_date');
            $table->decimal('new_rent_amount', 10, 2);
            $table->date('renewal_date');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_renewals');
        Schema::dropIfExists('lease_co_tenants');
        Schema::dropIfExists('leases');
    }
};
