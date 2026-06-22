<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\LateFee;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class BillingDashboardController extends Controller
{
    public function index(): Response
    {
        $now       = Carbon::now();
        $thisMonth = $now->copy()->startOfMonth();

        $monthlyRevenue = Payment::where('status', PaymentStatus::Completed)
            ->whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->sum('amount');

        $outstandingBalance = Invoice::whereIn('status', [
            InvoiceStatus::Sent->value,
            InvoiceStatus::Partial->value,
            InvoiceStatus::Overdue->value,
        ])->sum('balance_due');

        $collectedPayments = Payment::where('status', PaymentStatus::Completed)
            ->whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->count();

        $overdueCount = Invoice::where('status', InvoiceStatus::Overdue)->count();

        $lateFeesCollected = LateFee::whereMonth('applied_at', $now->month)
            ->whereYear('applied_at', $now->year)
            ->sum('amount');

        $paidInvoicesCount = Invoice::where('status', InvoiceStatus::Paid)
            ->whereMonth('paid_at', $now->month)
            ->whereYear('paid_at', $now->year)
            ->count();

        $revenueTrend = $this->buildRevenueTrend();

        $recentInvoices = Invoice::with(['lease.rentalTenant', 'lease.unit.property'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($inv) => $this->transformInvoiceRow($inv));

        $recentPayments = Payment::with(['invoice', 'lease.rentalTenant'])
            ->latest('payment_date')
            ->limit(10)
            ->get()
            ->map(fn ($pay) => $this->transformPaymentRow($pay));

        return Inertia::render('billing/Dashboard', [
            'stats' => [
                'monthly_revenue'    => (float) $monthlyRevenue,
                'outstanding_balance' => (float) $outstandingBalance,
                'collected_payments' => $collectedPayments,
                'overdue_count'      => $overdueCount,
                'late_fees_collected' => (float) $lateFeesCollected,
                'paid_invoices_count' => $paidInvoicesCount,
            ],
            'revenue_trend'   => $revenueTrend,
            'recent_invoices' => $recentInvoices,
            'recent_payments' => $recentPayments,
        ]);
    }

    private function buildRevenueTrend(): array
    {
        $months = collect(range(5, 0))->map(function (int $monthsAgo) {
            $month = Carbon::now()->subMonths($monthsAgo);

            $revenue = Payment::where('status', PaymentStatus::Completed)
                ->whereMonth('payment_date', $month->month)
                ->whereYear('payment_date', $month->year)
                ->sum('amount');

            return [
                'month'   => $month->format('M Y'),
                'revenue' => (float) $revenue,
            ];
        });

        return $months->values()->all();
    }

    private function transformInvoiceRow(Invoice $invoice): array
    {
        $lease    = $invoice->lease;
        $tenant   = $lease?->rentalTenant;
        $property = $lease?->unit?->property;

        return [
            'id'             => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'tenant_name'    => $tenant?->full_name ?? '—',
            'property_name'  => $property?->name ?? '—',
            'total_amount'   => (float) $invoice->total_amount,
            'balance_due'    => (float) $invoice->balance_due,
            'due_date'       => $invoice->due_date->toDateString(),
            'status'         => $invoice->status->value,
            'status_label'   => $invoice->status->label(),
        ];
    }

    private function transformPaymentRow(Payment $payment): array
    {
        return [
            'id'             => $payment->id,
            'payment_number' => $payment->payment_number,
            'invoice_number' => $payment->invoice?->invoice_number,
            'tenant_name'    => $payment->lease?->rentalTenant?->full_name ?? '—',
            'amount'         => (float) $payment->amount,
            'payment_method' => $payment->payment_method->value,
            'method_label'   => $payment->payment_method->label(),
            'payment_date'   => $payment->payment_date->toDateString(),
            'status'         => $payment->status->value,
            'status_label'   => $payment->status->label(),
        ];
    }
}
