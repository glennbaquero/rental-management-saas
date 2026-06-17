<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->string('occupation')->nullable();
            $table->string('employer')->nullable();
            $table->decimal('monthly_income', 10, 2)->nullable();
            $table->string('profile_photo')->nullable();
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');
            $table->text('notes')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tenant_id_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rental_tenant_id')->constrained('rental_tenants')->cascadeOnDelete();
            $table->enum('type', ['national_id', 'passport', 'drivers_license', 'sss', 'tin', 'other']);
            $table->string('document_number')->nullable();
            $table->string('issued_by')->nullable();
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rental_tenant_id')->constrained('rental_tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship');
            $table->string('phone', 30);
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_contacts');
        Schema::dropIfExists('tenant_id_documents');
        Schema::dropIfExists('rental_tenants');
    }
};
