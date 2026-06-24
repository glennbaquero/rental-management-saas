<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_widgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('key')->unique();
            $table->text('description')->nullable();
            $table->json('roles');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('user_dashboard_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('widget_key');
            $table->boolean('is_visible')->default(true);
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'widget_key']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_dashboard_preferences');
        Schema::dropIfExists('dashboard_widgets');
    }
};
