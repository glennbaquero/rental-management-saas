<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('transaction_id')->nullable()->after('reference_number');
            $table->string('proof_of_payment')->nullable()->after('notes');
            $table->timestamp('verified_at')->nullable()->after('proof_of_payment');
            $table->foreignUuid('verified_by')->nullable()->constrained('users')->nullOnDelete()->after('verified_at');
            $table->text('rejection_reason')->nullable()->after('verified_by');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['transaction_id', 'proof_of_payment', 'verified_at', 'rejection_reason']);
        });
    }
};
