<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->foreignUuid('building_id')->nullable()->constrained()->nullOnDelete()->after('property_id');
            $table->unsignedTinyInteger('max_occupants')->default(2)->after('area_sqm');

            $table->index('building_id');
            $table->index('status');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->enum('status', ['available', 'occupied', 'reserved', 'maintenance', 'unavailable'])
                  ->default('available')
                  ->after('deposit_amount');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropForeign(['building_id']);
            $table->dropIndex(['building_id']);
            $table->dropIndex(['status']);
            $table->dropColumn(['building_id', 'max_occupants', 'status']);
            $table->enum('status', ['available', 'occupied', 'maintenance', 'reserved'])->default('available');
        });
    }
};
