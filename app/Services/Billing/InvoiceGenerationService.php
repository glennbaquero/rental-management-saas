<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\BillingCycle;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\BillingSettings;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceGenerationService
{
    public function shouldGenerateToday(Lease $lease, Carbon $today): bool
    {
        if (! $lease->isActive()) {
            return false;
        }

        $dueDate = $this->calculateDueDate($lease, $today);

        if ($lease->generate_days_before !== null) {
            $generateOn = $dueDate->copy()->subDays($lease->generate_days_before);
            return $today->isSameDay($generateOn);
        }

        return $today->isSameDay($dueDate);
    }

    public function generateForLease(Lease $lease, Carbon $billingDate): Invoice
    {
        $settings       = BillingSettings::first() ?? new BillingSettings;
        $dueDate        = $this->calculateDueDate($lease, $billingDate);
        $issueDate      = $dueDate->copy()->subDays($lease->issue_date_offset ?? 0);
        $billingStart   = $this->billingPeriodStart($lease, $billingDate);
        $billingEnd     = $this->billingPeriodEnd($lease, $billingDate);
        $invoiceNumber  = $this->buildInvoiceNumber($settings);

        return DB::transaction(function () use ($lease, $dueDate, $issueDate, $billingStart, $billingEnd, $invoiceNumber) {
            $invoice = Invoice::create([
                'lease_id'             => $lease->id,
                'invoice_number'       => $invoiceNumber,
                'type'                 => InvoiceType::Rent,
                'status'               => InvoiceStatus::Draft,
                'billing_period_start' => $billingStart,
                'billing_period_end'   => $billingEnd,
                'issue_date'           => $issueDate,
                'due_date'             => $dueDate,
                'subtotal'             => $lease->rent_amount,
                'tax_amount'           => 0,
                'late_fee_amount'      => 0,
                'discount_amount'      => 0,
                'total_amount'         => $lease->rent_amount,
                'paid_amount'          => 0,
                'balance_due'          => $lease->rent_amount,
                'created_by'           => null,
            ]);

            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => 'Monthly Rent — ' . $billingStart->format('M d') . ' to ' . $billingEnd->format('M d, Y'),
                'quantity'    => 1,
                'unit_price'  => $lease->rent_amount,
                'total_price' => $lease->rent_amount,
                'type'        => 'rent',
            ]);

            return $invoice;
        });
    }

    public function calculateDueDate(Lease $lease, Carbon $billingDate): Carbon
    {
        $billingDay = $lease->billing_day ?? 1;
        $month      = $billingDate->copy()->startOfMonth();

        $billingCycle = $lease->billing_cycle ?? BillingCycle::Monthly;

        $dueMonth = match ($billingCycle) {
            BillingCycle::Monthly    => $month->addMonth(),
            BillingCycle::Quarterly  => $month->addMonths(3),
            BillingCycle::SemiAnnual => $month->addMonths(6),
            BillingCycle::Annual     => $month->addYear(),
        };

        $maxDay = $dueMonth->daysInMonth;

        return $dueMonth->setDay(min($billingDay, $maxDay));
    }

    public function buildInvoiceNumber(BillingSettings $settings): string
    {
        $prefix  = $settings->invoice_prefix ?? 'INV';
        $format  = $settings->invoice_number_format ?? '{PREFIX}-{YEAR}-{SEQ5}';
        $year    = now()->year;
        $last    = Invoice::whereYear('created_at', $year)->lockForUpdate()->count();
        $seq     = str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);

        return str_replace(
            ['{PREFIX}', '{YEAR}', '{SEQ5}', '{SEQ4}'],
            [$prefix, $year, $seq, str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT)],
            $format
        );
    }

    private function billingPeriodStart(Lease $lease, Carbon $billingDate): Carbon
    {
        $billingDay = $lease->billing_day ?? 1;
        $month      = $billingDate->copy()->startOfMonth();
        $maxDay     = $month->daysInMonth;

        return $month->setDay(min($billingDay, $maxDay));
    }

    private function billingPeriodEnd(Lease $lease, Carbon $billingDate): Carbon
    {
        $billingCycle = $lease->billing_cycle ?? BillingCycle::Monthly;
        $start        = $this->billingPeriodStart($lease, $billingDate);

        return $start->copy()->addMonths($billingCycle->monthsPerCycle())->subDay();
    }
}
