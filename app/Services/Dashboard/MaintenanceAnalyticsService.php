<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceTicket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MaintenanceAnalyticsService
{
    public function getTicketStats(): array
    {
        $tenantId = tenant('id');

        return Cache::store('file')->remember("dashboard:maintenance:{$tenantId}", 120, function () {
            $open       = MaintenanceTicket::where('status', MaintenanceStatus::Open)->count();
            $assigned   = MaintenanceTicket::where('status', MaintenanceStatus::Assigned)->count();
            $inProgress = MaintenanceTicket::where('status', MaintenanceStatus::InProgress)->count();
            $resolved   = MaintenanceTicket::where('status', MaintenanceStatus::Resolved)->count();
            $emergency  = MaintenanceTicket::where('priority', MaintenancePriority::Emergency)
                ->whereNotIn('status', [
                    MaintenanceStatus::Resolved,
                    MaintenanceStatus::Completed,
                    MaintenanceStatus::Cancelled,
                ])
                ->count();

            $avgResolutionHours = MaintenanceTicket::where('status', MaintenanceStatus::Completed)
                ->whereMonth('updated_at', Carbon::now()->month)
                ->whereNotNull('created_at')
                ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at) / 3600) as avg_hours')
                ->value('avg_hours');

            $totalCostThisMonth = (float) MaintenanceCost::whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('amount');

            return [
                'open'                  => $open,
                'assigned'              => $assigned,
                'in_progress'           => $inProgress,
                'resolved'              => $resolved,
                'emergency'             => $emergency,
                'avg_resolution_hours'  => round((float) ($avgResolutionHours ?? 0), 1),
                'total_cost_this_month' => $totalCostThisMonth,
            ];
        });
    }

    public function getAssignedTickets(string $userId, int $limit = 10): array
    {
        return MaintenanceTicket::with(['property', 'unit', 'rentalTenant', 'primaryAssignment.user'])
            ->whereHas('assignments', fn ($q) => $q->where('user_id', $userId))
            ->whereNotIn('status', [MaintenanceStatus::Completed, MaintenanceStatus::Cancelled])
            ->orderByRaw("CASE
                WHEN priority = 'emergency' THEN 1
                WHEN priority = 'urgent'    THEN 2
                WHEN priority = 'high'      THEN 3
                WHEN priority = 'medium'    THEN 4
                ELSE 5 END")
            ->limit($limit)
            ->get()
            ->map(fn ($ticket) => $this->transformTicket($ticket))
            ->all();
    }

    public function getRecentTickets(int $limit = 10): array
    {
        return MaintenanceTicket::with(['property', 'unit', 'rentalTenant', 'primaryAssignment.user'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn ($ticket) => $this->transformTicket($ticket))
            ->all();
    }

    public function getMonthlyRequestTrend(int $months = 6): array
    {
        $tenantId = tenant('id');

        return Cache::store('file')->remember("dashboard:maint_trend:{$tenantId}", 300, function () use ($months) {
            return collect(range($months - 1, 0))->map(function (int $monthsAgo) {
                $month = Carbon::now()->subMonths($monthsAgo);

                $count = MaintenanceTicket::whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->count();

                return [
                    'month' => $month->format('M Y'),
                    'label' => $month->format('M'),
                    'value' => $count,
                ];
            })->values()->all();
        });
    }

    public function getPriorityDistribution(): array
    {
        $colors = [
            MaintenancePriority::Low->value       => '#22c55e',
            MaintenancePriority::Medium->value    => '#f59e0b',
            MaintenancePriority::High->value      => '#f97316',
            MaintenancePriority::Urgent->value    => '#ef4444',
            MaintenancePriority::Emergency->value => '#dc2626',
        ];

        return MaintenanceTicket::whereNotIn('status', [MaintenanceStatus::Completed, MaintenanceStatus::Cancelled])
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->groupBy('priority')
            ->get()
            ->map(fn ($row) => [
                'label' => MaintenancePriority::from($row->priority)->label(),
                'value' => (int) $row->count,
                'color' => $colors[$row->priority] ?? '#94a3b8',
            ])
            ->filter(fn ($item) => $item['value'] > 0)
            ->values()
            ->all();
    }

    private function transformTicket(MaintenanceTicket $ticket): array
    {
        return [
            'id'            => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'title'         => $ticket->title,
            'property_name' => $ticket->property?->name ?? '—',
            'unit_number'   => $ticket->unit?->unit_number ?? '—',
            'tenant_name'   => $ticket->rentalTenant?->full_name ?? '—',
            'priority'      => $ticket->priority->value,
            'priority_label' => $ticket->priority->label(),
            'priority_color' => $ticket->priority->color(),
            'status'        => $ticket->status->value,
            'status_label'  => $ticket->status->label(),
            'assigned_to'   => $ticket->primaryAssignment?->user?->name
                ?? $ticket->primaryAssignment?->contractor_name
                ?? 'Unassigned',
            'created_at'    => $ticket->created_at->toDateString(),
        ];
    }
}
