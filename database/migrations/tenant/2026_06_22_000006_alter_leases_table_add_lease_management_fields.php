<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leases', function (Blueprint $table) {
            $table->foreignUuid('building_id')->nullable()->after('unit_id')->constrained('buildings')->nullOnDelete();
            $table->enum('lease_type', ['monthly', 'yearly', 'fixed_term', 'commercial'])->default('monthly')->after('lease_number');
            $table->date('move_in_date')->nullable()->after('end_date');
            $table->date('move_out_date')->nullable()->after('move_in_date');
            $table->decimal('advance_payment', 10, 2)->default(0)->after('deposit_amount');
            $table->decimal('utility_deposit', 10, 2)->default(0)->after('advance_payment');
            $table->decimal('parking_fee', 10, 2)->default(0)->after('utility_deposit');
            $table->decimal('other_charges', 10, 2)->default(0)->after('parking_fee');
            $table->string('currency', 3)->default('PHP')->after('other_charges');
        });

        DB::statement("ALTER TABLE leases MODIFY COLUMN status ENUM('draft','pending','active','expiring_soon','expired','renewed','terminated','cancelled') DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE leases MODIFY COLUMN status ENUM('pending','active','expired','terminated') DEFAULT 'pending'");

        Schema::table('leases', function (Blueprint $table) {
            $table->dropForeign(['building_id']);
            $table->dropColumn(['building_id', 'lease_type', 'move_in_date', 'move_out_date', 'advance_payment', 'utility_deposit', 'parking_fee', 'other_charges', 'currency']);
        });
    }
};
