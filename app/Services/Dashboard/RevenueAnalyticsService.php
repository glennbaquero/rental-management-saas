<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\LeaseDeposit;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RevenueAnalyticsService
{
    public function getMonthlyRevenue(): float
    {
        $now = Carbon::now();

        return (float) Payment::where('status', PaymentStatus::Completed)
            ->whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->sum('amount');
    }

    public function getPreviousMonthRevenue(): float
    {
        $prev = Carbon::now()->subMonth();

        return (float) Payment::where('status', PaymentStatus::Completed)
            ->whereMonth('payment_date', $prev->month)
            ->whereYear('payment_date', $prev->year)
            ->sum('amount');
    }

    public function getOutstandingBalance(): float
    {
        return (float) Invoice::whereIn('status', [
            InvoiceStatus::Sent->value,
            InvoiceStatus::Partial->value,
            InvoiceStatus::Overdue->value,
        ])->sum('balance_due');
    }

    public function getOverdueCount(): int
    {
        return Invoice::where('status', InvoiceStatus::Overdue)->count();
    }

    public function getOverduePreviousMonth(): int
    {
        $prev = Carbon::now()->subMonth();

        return Invoice::where('status', InvoiceStatus::Overdue)
            ->whereMonth('due_date', $prev->month)
            ->whereYear('due_date', $prev->year)
            ->count();
    }

    public function getDepositsHeld(): float
    {
        return (float) LeaseDeposit::whereIn('status', ['pending', 'held'])->sum('amount');
    }

    public function getMonthlyRevenueTrend(int $months = 6): array
    {
        $tenantId = tenant('id');

        return Cache::store('file')->remember("dashboard:revenue:{$tenantId}", 300, function () use ($months) {
            return collect(range($months - 1, 0))->map(function (int $monthsAgo) {
                $month = Carbon::now()->subMonths($monthsAgo);

                $revenue = Payment::where('status', PaymentStatus::Completed)
                    ->whereMonth('payment_date', $month->month)
                    ->whereYear('payment_date', $month->year)
                    ->sum('amount');

                return [
                    'month'   => $month->format('M Y'),
                    'value'   => (float) $revenue,
                    'label'   => $month->format('M'),
                ];
            })->values()->all();
        });
    }

    public function getRevenueByProperty(): array
    {
        $tenantId = tenant('id');

        return Cache::store('file')->remember("dashboard:revenue_by_property:{$tenantId}", 300, function () {
            return DB::table('payments')
                ->join('leases', 'payments.lease_id', '=', 'leases.id')
                ->join('units', 'leases.unit_id', '=', 'units.id')
                ->join('properties', 'units.property_id', '=', 'properties.id')
                ->where('payments.status', PaymentStatus::Completed->value)
                ->whereMonth('payments.payment_date', Carbon::now()->month)
                ->whereYear('payments.payment_date', Carbon::now()->year)
                ->groupBy('properties.id', 'properties.name')
                ->select(
                    'properties.id',
                    'properties.name',
                    DB::raw('SUM(payments.amount) as revenue')
                )
                ->orderByDesc('revenue')
                ->limit(8)
                ->get()
                ->map(fn ($row) => [
                    'id'      => $row->id,
                    'label'   => $row->name,
                    'value'   => (float) $row->revenue,
                ])
                ->all();
        });
    }

    public function getPaymentMethodBreakdown(): array
    {
        $tenantId = tenant('id');

        return Cache::store('file')->remember("dashboard:payment_methods:{$tenantId}", 300, function () {
            $colors = [
                PaymentMethod::Cash->value         => '#22c55e',
                PaymentMethod::BankTransfer->value  => '#3b82f6',
                PaymentMethod::GCash->value         => '#a855f7',
                PaymentMethod::PayMaya->value       => '#14b8a6',
                PaymentMethod::Stripe->value        => '#6366f1',
                PaymentMethod::Check->value         => '#f59e0b',
                PaymentMethod::Other->value         => '#94a3b8',
            ];

            return Payment::where('status', PaymentStatus::Completed)
                ->whereMonth('payment_date', Carbon::now()->month)
                ->whereYear('payment_date', Carbon::now()->year)
                ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
                ->groupBy('payment_method')
                ->get()
                ->map(fn ($row) => [
                    'label' => PaymentMethod::from($row->payment_method)->label(),
                    'value' => (float) $row->total,
                    'count' => (int) $row->count,
                    'color' => $colors[$row->payment_method] ?? '#94a3b8',
                ])
                ->all();
        });
    }

    public function getRecentInvoices(int $limit = 8): array
    {
        return Invoice::with(['lease.rentalTenant', 'lease.unit.property'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($inv) => [
                'id'             => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'tenant_name'    => $inv->lease?->rentalTenant?->full_name ?? '—',
                'property_name'  => $inv->lease?->unit?->property?->name ?? '—',
                'total_amount'   => (float) $inv->total_amount,
                'balance_due'    => (float) $inv->balance_due,
                'due_date'       => $inv->due_date->toDateString(),
                'status'         => $inv->status->value,
                'status_label'   => $inv->status->label(),
            ])
            ->all();
    }

    public function getRentCollectionRate(): float
    {
        $now = Carbon::now();

        $totalDue = (float) Invoice::whereMonth('due_date', $now->month)
            ->whereYear('due_date', $now->year)
            ->whereNotIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Void->value])
            ->sum('total_amount');

        if ($totalDue <= 0) {
            return 0;
        }

        $collected = (float) Payment::where('status', PaymentStatus::Completed)
            ->whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->sum('amount');

        return round(min(($collected / $totalDue) * 100, 100), 1);
    }
}
