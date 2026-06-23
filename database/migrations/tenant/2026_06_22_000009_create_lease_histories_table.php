<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lease_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lease_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 100);
            $table->text('description');
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('lease_id');
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lease_histories');
    }
};
