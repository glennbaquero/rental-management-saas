<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lease_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('rental_tenant_id')->constrained('rental_tenants')->restrictOnDelete();
            $table->foreignUuid('unit_id')->constrained()->restrictOnDelete();
            $table->date('entry_date');
            $table->enum('type', ['debit', 'credit']);
            $table->enum('category', ['rent', 'deposit', 'payment', 'late_fee', 'utility', 'refund', 'adjustment']);
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->foreignUuid('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['lease_id', 'entry_date']);
            $table->index(['rental_tenant_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};
