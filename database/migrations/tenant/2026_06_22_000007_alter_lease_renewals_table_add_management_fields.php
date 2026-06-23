<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lease_renewals', function (Blueprint $table) {
            $table->enum('renewal_status', ['pending', 'approved', 'rejected', 'completed'])->default('pending')->after('notes');
            $table->text('reason')->nullable()->after('renewal_status');
            $table->date('previous_start_date')->nullable()->after('previous_end_date');
            $table->foreignUuid('renewed_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();

            $table->index(['lease_id', 'renewal_status']);
        });
    }

    public function down(): void
    {
        Schema::table('lease_renewals', function (Blueprint $table) {
            $table->dropIndex(['lease_id', 'renewal_status']);
            $table->dropForeign(['renewed_by']);
            $table->dropColumn(['renewal_status', 'reason', 'previous_start_date', 'renewed_by']);
        });
    }
};
