<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\UnitStatus;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OccupancyService
{
    public function getOccupancyStats(): array
    {
        $tenantId = tenant('id');

        return Cache::store('file')->remember("dashboard:occupancy:{$tenantId}", 300, function () {
            $total    = Unit::count();
            $occupied = Unit::where('status', UnitStatus::Occupied)->count();
            $vacant   = Unit::where('status', UnitStatus::Available)->count();
            $rate     = $total > 0 ? round(($occupied / $total) * 100, 1) : 0;

            return [
                'total_units'    => $total,
                'occupied_units' => $occupied,
                'vacant_units'   => $vacant,
                'occupancy_rate' => $rate,
            ];
        });
    }

    public function getPropertyOccupancyList(): array
    {
        $tenantId = tenant('id');

        return Cache::store('file')->remember("dashboard:prop_occupancy:{$tenantId}", 300, function () {
            $properties = Property::with(['units'])->get();

            return $properties->map(function (Property $property) {
                $total    = $property->units->count();
                $occupied = $property->units->where('status', UnitStatus::Occupied)->count();
                $vacant   = $property->units->where('status', UnitStatus::Available)->count();
                $rate     = $total > 0 ? round(($occupied / $total) * 100, 1) : 0;

                $monthlyIncome = DB::table('payments')
                    ->join('leases', 'payments.lease_id', '=', 'leases.id')
                    ->join('units', 'leases.unit_id', '=', 'units.id')
                    ->where('units.property_id', $property->id)
                    ->where('payments.status', 'completed')
                    ->whereMonth('payments.payment_date', Carbon::now()->month)
                    ->whereYear('payments.payment_date', Carbon::now()->year)
                    ->sum('payments.amount');

                return [
                    'id'             => $property->id,
                    'name'           => $property->name,
                    'total_units'    => $total,
                    'occupied_units' => $occupied,
                    'vacant_units'   => $vacant,
                    'occupancy_rate' => $rate,
                    'monthly_income' => (float) $monthlyIncome,
                ];
            })
            ->sortByDesc('occupancy_rate')
            ->values()
            ->all();
        });
    }

    public function getUnitsByType(): array
    {
        $tenantId = tenant('id');

        return Cache::store('file')->remember("dashboard:units_by_type:{$tenantId}", 300, function () {
            return Unit::select('type', DB::raw('COUNT(*) as count'))
                ->groupBy('type')
                ->get()
                ->map(fn ($row) => [
                    'label' => ucfirst((string) $row->type),
                    'value' => (int) $row->count,
                ])
                ->all();
        });
    }

    public function getOccupancyTrend(int $months = 6): array
    {
        $tenantId = tenant('id');

        return Cache::store('file')->remember("dashboard:occupancy_trend:{$tenantId}", 300, function () use ($months) {
            $totalUnits = Unit::count();

            return collect(range($months - 1, 0))->map(function (int $monthsAgo) use ($totalUnits) {
                $month = Carbon::now()->subMonths($monthsAgo);

                $occupied = DB::table('leases')
                    ->where('status', 'active')
                    ->whereDate('start_date', '<=', $month->copy()->endOfMonth())
                    ->whereDate('end_date', '>=', $month->copy()->startOfMonth())
                    ->count();

                $rate = $totalUnits > 0 ? round(($occupied / $totalUnits) * 100, 1) : 0;

                return [
                    'month' => $month->format('M Y'),
                    'label' => $month->format('M'),
                    'value' => $rate,
                ];
            })->values()->all();
        });
    }
}
