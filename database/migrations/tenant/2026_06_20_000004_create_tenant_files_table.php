<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rental_tenant_id')->constrained('rental_tenants')->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', [
                'lease_agreement',
                'proof_of_income',
                'employment_certificate',
                'utility_bill',
                'police_clearance',
                'other',
            ])->default('other');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['rental_tenant_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_files');
    }
};
