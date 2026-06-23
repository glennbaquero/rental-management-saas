<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ticket_id')->constrained('maintenance_tickets')->cascadeOnDelete();
            $table->string('event_type');
            $table->string('description');
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index('ticket_id');
            $table->index(['ticket_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_histories');
    }
};
