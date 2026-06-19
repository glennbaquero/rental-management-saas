<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            $table->string('slug', 80)->nullable()->unique()->after('name');
            $table->boolean('is_system')->default(false)->after('category');

            $table->index('category');
            $table->index('is_system');
        });
    }

    public function down(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['is_system']);
            $table->dropColumn(['slug', 'is_system']);
        });
    }
};
