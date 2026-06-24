<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\LeaseStatus;
use App\Models\Lease;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class LeaseAnalyticsService
{
    public function getLeaseStats(): array
    {
        $tenantId = tenant('id');

        return Cache::store('file')->remember("dashboard:leases:{$tenantId}", 300, function () {
            $active          = Lease::where('status', LeaseStatus::Active)->count();
            $expiringSoon    = Lease::where('status', LeaseStatus::Active)
                ->whereBetween('end_date', [Carbon::today(), Carbon::today()->addDays(30)])
                ->count();
            $expired         = Lease::where('status', LeaseStatus::Expired)->count();
            $renewed         = Lease::where('status', LeaseStatus::Renewed)->count();
            $terminated      = Lease::where('status', LeaseStatus::Terminated)->count();
            $expiringThisMonth = Lease::where('status', LeaseStatus::Active)
                ->whereMonth('end_date', Carbon::now()->month)
                ->whereYear('end_date', Carbon::now()->year)
                ->count();

            return [
                'active'              => $active,
                'expiring_soon'       => $expiringSoon,
                'expiring_this_month' => $expiringThisMonth,
                'expired'             => $expired,
                'renewed'             => $renewed,
                'terminated'          => $terminated,
            ];
        });
    }

    public function getExpiringLeases(int $limit = 10): array
    {
        return Lease::with(['rentalTenant', 'unit.property', 'unit.building'])
            ->where('status', LeaseStatus::Active)
            ->whereBetween('end_date', [Carbon::today(), Carbon::today()->addDays(60)])
            ->orderBy('end_date')
            ->limit($limit)
            ->get()
            ->map(fn (Lease $lease) => [
                'id'             => $lease->id,
                'lease_number'   => $lease->lease_number,
                'tenant_name'    => $lease->rentalTenant?->full_name ?? '—',
                'property_name'  => $lease->unit?->property?->name ?? '—',
                'unit_number'    => $lease->unit?->unit_number ?? '—',
                'end_date'       => $lease->end_date->toDateString(),
                'days_remaining' => (int) $lease->end_date->diffInDays(Carbon::today(), false) * -1,
                'status'         => $lease->status->value,
                'status_label'   => $lease->status->label(),
            ])
            ->all();
    }

    public function getLeaseStatusBreakdown(): array
    {
        $colors = [
            LeaseStatus::Active->value       => '#22c55e',
            LeaseStatus::ExpiringSoon->value  => '#f59e0b',
            LeaseStatus::Expired->value       => '#ef4444',
            LeaseStatus::Renewed->value       => '#3b82f6',
            LeaseStatus::Terminated->value    => '#94a3b8',
            LeaseStatus::Pending->value       => '#a855f7',
            LeaseStatus::Draft->value         => '#64748b',
            LeaseStatus::Cancelled->value     => '#9ca3af',
        ];

        return Lease::select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'label' => LeaseStatus::from($row->status)->label(),
                'value' => (int) $row->count,
                'color' => $colors[$row->status] ?? '#94a3b8',
            ])
            ->filter(fn ($item) => $item['value'] > 0)
            ->values()
            ->all();
    }

    public function getUpcomingMoveOuts(int $limit = 5): array
    {
        return Lease::with(['rentalTenant', 'unit.property'])
            ->whereNotNull('move_out_date')
            ->whereDate('move_out_date', '>=', Carbon::today())
            ->orderBy('move_out_date')
            ->limit($limit)
            ->get()
            ->map(fn (Lease $lease) => [
                'id'            => $lease->id,
                'tenant_name'   => $lease->rentalTenant?->full_name ?? '—',
                'property_name' => $lease->unit?->property?->name ?? '—',
                'unit_number'   => $lease->unit?->unit_number ?? '—',
                'move_out_date' => $lease->move_out_date?->toDateString(),
            ])
            ->all();
    }
}
