<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_costs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ticket_id')->constrained('maintenance_tickets')->cascadeOnDelete();
            $table->enum('cost_type', ['labor', 'material', 'contractor_fee', 'transportation', 'miscellaneous']);
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
            $table->string('receipt_path')->nullable();
            $table->foreignUuid('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'status']);
            $table->index(['ticket_id', 'cost_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_costs');
    }
};
