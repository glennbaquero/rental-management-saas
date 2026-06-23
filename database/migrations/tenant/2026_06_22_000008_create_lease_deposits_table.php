<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lease_deposits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lease_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['security', 'advance', 'utility']);
            $table->decimal('amount', 10, 2);
            $table->date('payment_date')->nullable();
            $table->enum('status', ['pending', 'paid', 'partially_paid', 'refunded'])->default('pending');
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->decimal('deduction_amount', 10, 2)->nullable();
            $table->date('refund_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['lease_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_deposits');
    }
};
