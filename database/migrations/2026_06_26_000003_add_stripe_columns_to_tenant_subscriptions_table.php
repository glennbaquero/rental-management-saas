<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->string('stripe_price_id')->nullable()->after('stripe_subscription_id');
            $table->string('stripe_product_id')->nullable()->after('stripe_price_id');
            $table->unsignedInteger('quantity')->default(1)->after('stripe_product_id');
            $table->timestamp('cancel_at')->nullable()->after('canceled_at');
            $table->timestamp('paused_at')->nullable()->after('cancel_at');
            $table->json('metadata')->nullable()->after('paused_at');
        });
    }

    public function down(): void
    {
        Schema::table('tenant_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['stripe_price_id', 'stripe_product_id', 'quantity', 'cancel_at', 'paused_at', 'metadata']);
        });
    }
};
