<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('unit_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('rental_tenant_id')->nullable()->constrained('rental_tenants')->nullOnDelete();
            $table->string('title');
            $table->text('description');
            $table->enum('category', ['plumbing', 'electrical', 'hvac', 'appliance', 'structural', 'pest_control', 'carpentry', 'other']);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'on_hold', 'resolved', 'closed'])->default('open');
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->date('scheduled_date')->nullable();
            $table->timestamp('resolved_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['unit_id', 'status']);
            $table->index(['assigned_to', 'status']);
        });

        Schema::create('maintenance_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('maintenance_request_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('comment');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_comments');
        Schema::dropIfExists('maintenance_requests');
    }
};
