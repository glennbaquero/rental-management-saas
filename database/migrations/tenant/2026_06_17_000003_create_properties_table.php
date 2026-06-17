<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('address');
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('zip', 20)->nullable();
            $table->string('country', 2)->default('PH');
            $table->enum('type', ['residential', 'commercial', 'mixed'])->default('residential');
            $table->text('description')->nullable();
            $table->string('photo')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
