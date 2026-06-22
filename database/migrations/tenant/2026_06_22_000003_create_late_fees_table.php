<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('late_fees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('lease_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['fixed', 'percentage']);
            $table->decimal('rate', 10, 2)->comment('Fixed amount or percentage value');
            $table->decimal('amount', 10, 2)->comment('Computed amount applied');
            $table->unsignedInteger('days_overdue')->default(0);
            $table->timestamp('applied_at');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('invoice_id');
            $table->index('lease_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('late_fees');
    }
};
