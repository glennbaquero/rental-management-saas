<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->string('code', 50)->unique()->nullable()->after('name');
            $table->string('province', 100)->nullable()->after('city');
            $table->string('postal_code', 20)->nullable()->after('zip');
            $table->decimal('latitude', 10, 8)->nullable()->after('postal_code');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->enum('status', ['active', 'under_maintenance', 'inactive'])->default('active')->after('longitude');
            $table->string('featured_image')->nullable()->after('photo');

            $table->index('status');
            $table->index('city');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->enum('type', [
                'apartment', 'condominium', 'dormitory', 'house',
                'townhouse', 'commercial', 'mixed_use',
            ])->default('apartment')->after('code');

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['city']);
            $table->dropIndex(['type']);
            $table->dropColumn(['code', 'province', 'postal_code', 'latitude', 'longitude', 'status', 'featured_image', 'type']);
            $table->enum('type', ['residential', 'commercial', 'mixed'])->default('residential')->after('name');
        });
    }
};
