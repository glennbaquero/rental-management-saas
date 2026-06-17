<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('lease_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('rental_tenant_id')->constrained('rental_tenants')->restrictOnDelete();
            $table->string('payment_number')->unique();
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['cash', 'bank_transfer', 'gcash', 'paymaya', 'stripe', 'check', 'other']);
            $table->string('reference_number')->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->foreignUuid('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('completed');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['lease_id', 'status']);
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
