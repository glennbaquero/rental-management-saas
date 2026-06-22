<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\LateFeeType;
use App\Models\BillingSettings;
use App\Models\Invoice;
use App\Models\LateFee;
use Illuminate\Support\Carbon;

class LateFeeService
{
    public function hasAlreadyApplied(Invoice $invoice): bool
    {
        return $invoice->lateFees()->whereDate('applied_at', today())->exists();
    }

    public function calculate(Invoice $invoice, BillingSettings $settings): float
    {
        if ($settings->late_fee_type === LateFeeType::Percentage) {
            return round((float) $invoice->balance_due * ($settings->late_fee_percentage / 100), 2);
        }

        return (float) $settings->late_fee_amount;
    }

    public function applyToInvoice(Invoice $invoice, BillingSettings $settings, ?string $createdBy = null): ?LateFee
    {
        if (! $settings->late_fee_enabled) {
            return null;
        }

        if (! in_array($invoice->status, [InvoiceStatus::Sent, InvoiceStatus::Partial, InvoiceStatus::Overdue])) {
            return null;
        }

        $daysOverdue = (int) now()->diffInDays($invoice->due_date);
        if ($daysOverdue < $settings->apply_late_fee_after_days) {
            return null;
        }

        if ($this->hasAlreadyApplied($invoice)) {
            return null;
        }

        $amount = $this->calculate($invoice, $settings);
        if ($amount <= 0) {
            return null;
        }

        $lateFee = LateFee::create([
            'invoice_id'  => $invoice->id,
            'lease_id'    => $invoice->lease_id,
            'type'        => $settings->late_fee_type,
            'rate'        => $settings->late_fee_type === LateFeeType::Percentage
                ? $settings->late_fee_percentage
                : $settings->late_fee_amount,
            'amount'      => $amount,
            'days_overdue' => $daysOverdue,
            'applied_at'  => now(),
            'created_by'  => $createdBy,
        ]);

        $invoice->increment('late_fee_amount', $amount);
        $invoice->increment('total_amount', $amount);
        $invoice->increment('balance_due', $amount);
        $invoice->update(['status' => InvoiceStatus::Overdue]);

        return $lateFee;
    }
}
