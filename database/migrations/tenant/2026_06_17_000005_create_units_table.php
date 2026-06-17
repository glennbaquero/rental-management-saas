<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('property_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('unit_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit_number');
            $table->string('floor')->nullable();
            $table->decimal('area_sqm', 8, 2)->nullable();
            $table->unsignedTinyInteger('bedrooms')->default(0);
            $table->unsignedTinyInteger('bathrooms')->default(0);
            $table->decimal('rent_amount', 10, 2)->default(0);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->enum('status', ['available', 'occupied', 'maintenance', 'reserved'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['property_id', 'unit_number']);
        });

        Schema::create('amenities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
        });

        Schema::create('amenity_property', function (Blueprint $table) {
            $table->foreignUuid('amenity_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('property_id')->constrained()->cascadeOnDelete();
            $table->primary(['amenity_id', 'property_id']);
        });

        Schema::create('amenity_unit', function (Blueprint $table) {
            $table->foreignUuid('amenity_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('unit_id')->constrained()->cascadeOnDelete();
            $table->primary(['amenity_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amenity_unit');
        Schema::dropIfExists('amenity_property');
        Schema::dropIfExists('amenities');
        Schema::dropIfExists('units');
    }
};
