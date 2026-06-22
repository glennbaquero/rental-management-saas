<?php

namespace App\Models;

use App\Enums\LateFeeType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $currency
 * @property string $timezone
 * @property string $invoice_prefix
 * @property string $invoice_number_format
 * @property int $grace_period_days
 * @property bool $auto_generate_invoices
 * @property bool $auto_send_reminders
 * @property bool $late_fee_enabled
 * @property LateFeeType $late_fee_type
 * @property float $late_fee_amount
 * @property float $late_fee_percentage
 * @property int $apply_late_fee_after_days
 * @property bool $compound_monthly
 * @property array|null $reminder_days_before
 * @property array|null $reminder_channels
 */
#[Fillable([
    'currency', 'timezone', 'invoice_prefix', 'invoice_number_format',
    'grace_period_days', 'auto_generate_invoices', 'auto_send_reminders',
    'late_fee_enabled', 'late_fee_type', 'late_fee_amount', 'late_fee_percentage',
    'apply_late_fee_after_days', 'compound_monthly',
    'reminder_days_before', 'reminder_channels',
])]
class BillingSettings extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'late_fee_type'          => LateFeeType::class,
            'late_fee_amount'        => 'decimal:2',
            'late_fee_percentage'    => 'decimal:2',
            'auto_generate_invoices' => 'boolean',
            'auto_send_reminders'    => 'boolean',
            'late_fee_enabled'       => 'boolean',
            'compound_monthly'       => 'boolean',
            'grace_period_days'      => 'integer',
            'apply_late_fee_after_days' => 'integer',
            'reminder_days_before'   => 'array',
            'reminder_channels'      => 'array',
        ];
    }
}
