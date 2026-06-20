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
        Schema::table('tenant_id_documents', function (Blueprint $table) {
            $table->string('front_image')->nullable()->after('file_path');
            $table->string('back_image')->nullable()->after('front_image');
            $table->string('verification_status')->default('pending')->after('back_image');
            $table->index(['verification_status']);
        });

        DB::statement("ALTER TABLE tenant_id_documents MODIFY type ENUM('national_id','passport','drivers_license','sss','tin','residence_permit','other')");
    }

    public function down(): void
    {
        Schema::table('tenant_id_documents', function (Blueprint $table) {
            $table->dropIndex(['verification_status']);
            $table->dropColumn(['front_image', 'back_image', 'verification_status']);
        });

        DB::statement("ALTER TABLE tenant_id_documents MODIFY type ENUM('national_id','passport','drivers_license','sss','tin','other')");
    }
};
