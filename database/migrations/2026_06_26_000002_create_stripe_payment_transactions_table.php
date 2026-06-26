<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('tenant_id');
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_invoice_id')->nullable();
            $table->string('stripe_subscription_id')->nullable();
            $table->string('status');
            $table->char('currency', 3)->default('usd');
            $table->unsignedBigInteger('amount')->default(0);
            $table->unsignedBigInteger('amount_refunded')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index('tenant_id');
            $table->index(['tenant_id', 'status']);
            $table->index('stripe_payment_intent_id');
            $table->index('stripe_charge_id');
            $table->index('stripe_invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_payment_transactions');
    }
};
