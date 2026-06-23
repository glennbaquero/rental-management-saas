<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ticket_id')->constrained('maintenance_tickets')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('assignee_type', ['property_manager', 'maintenance_staff', 'external_contractor']);
            $table->string('contractor_name')->nullable();
            $table->string('contractor_contact')->nullable();
            $table->date('assigned_date');
            $table->date('estimated_completion')->nullable();
            $table->date('actual_completion')->nullable();
            $table->text('remarks')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['ticket_id', 'is_primary']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_assignments');
    }
};
