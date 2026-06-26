<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentTransactionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string|null $stripe_payment_intent_id
 * @property string|null $stripe_charge_id
 * @property string|null $stripe_invoice_id
 * @property string|null $stripe_subscription_id
 * @property PaymentTransactionStatus $status
 * @property string $currency
 * @property int $amount
 * @property int $amount_refunded
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $paid_at
 */
#[Fillable([
    'tenant_id',
    'stripe_payment_intent_id',
    'stripe_charge_id',
    'stripe_invoice_id',
    'stripe_subscription_id',
    'status',
    'currency',
    'amount',
    'amount_refunded',
    'metadata',
    'paid_at',
])]
class StripePaymentTransaction extends Model
{
    use HasUuids;

    protected $connection = 'mysql';

    protected function casts(): array
    {
        return [
            'status'          => PaymentTransactionStatus::class,
            'amount'          => 'integer',
            'amount_refunded' => 'integer',
            'metadata'        => 'array',
            'paid_at'         => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function getAmountInDecimalAttribute(): float
    {
        return $this->amount / 100;
    }
}
