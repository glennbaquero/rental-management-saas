<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_ratings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ticket_id')->constrained('maintenance_tickets')->cascadeOnDelete();
            $table->foreignUuid('rental_tenant_id')->nullable()->constrained('rental_tenants')->nullOnDelete();
            $table->tinyInteger('rating');
            $table->text('feedback')->nullable();
            $table->boolean('would_recommend')->nullable();
            $table->timestamp('rated_at');

            $table->unique('ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_ratings');
    }
};
