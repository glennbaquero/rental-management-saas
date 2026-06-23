<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ticket_number')->unique();
            $table->foreignUuid('property_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('building_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('unit_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('rental_tenant_id')->nullable()->constrained('rental_tenants')->nullOnDelete();
            $table->enum('category', [
                'plumbing', 'electrical', 'air_conditioning', 'appliance_repair',
                'internet_wifi', 'water_leak', 'painting', 'pest_control',
                'structural_damage', 'cleaning', 'security', 'other',
            ]);
            $table->string('title');
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent', 'emergency'])->default('low');
            $table->enum('status', [
                'open', 'assigned', 'in_progress', 'waiting_for_parts',
                'on_hold', 'resolved', 'completed', 'cancelled',
            ])->default('open');
            $table->timestamp('preferred_schedule')->nullable();
            $table->timestamp('date_submitted')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['property_id', 'status']);
            $table->index(['unit_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index('rental_tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_tickets');
    }
};
