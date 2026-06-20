<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('emergency_contacts', function (Blueprint $table) {
            $table->string('alternate_number', 30)->nullable()->after('phone');
            $table->boolean('is_primary')->default(false)->after('alternate_number');
            $table->index(['rental_tenant_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::table('emergency_contacts', function (Blueprint $table) {
            $table->dropIndex(['rental_tenant_id', 'is_primary']);
            $table->dropColumn(['alternate_number', 'is_primary']);
        });
    }
};
