<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenance;

use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceTicket;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceDashboardController extends Controller
{
    public function index(): Response
    {
        $now = Carbon::now();

        $openCount       = MaintenanceTicket::where('status', MaintenanceStatus::Open)->count();
        $assignedCount   = MaintenanceTicket::where('status', MaintenanceStatus::Assigned)->count();
        $inProgressCount = MaintenanceTicket::where('status', MaintenanceStatus::InProgress)->count();
        $resolvedCount   = MaintenanceTicket::where('status', MaintenanceStatus::Resolved)
            ->whereMonth('resolved_at', $now->month)
            ->whereYear('resolved_at', $now->year)
            ->count();
        $completedCount  = MaintenanceTicket::where('status', MaintenanceStatus::Completed)
            ->whereMonth('completed_at', $now->month)
            ->whereYear('completed_at', $now->year)
            ->count();
        $emergencyCount  = MaintenanceTicket::where('priority', MaintenancePriority::Emergency)
            ->whereNotIn('status', [MaintenanceStatus::Completed->value, MaintenanceStatus::Cancelled->value])
            ->count();

        $totalCostThisMonth = MaintenanceCost::whereHas(
            'ticket',
            fn ($q) => $q->whereMonth('created_at', $now->month)->whereYear('created_at', $now->year)
        )->sum('amount');

        $avgResolutionHours = MaintenanceTicket::whereNotNull('completed_at')
            ->whereMonth('completed_at', $now->month)
            ->whereYear('completed_at', $now->year)
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, date_submitted, completed_at)) as avg_hours')
            ->value('avg_hours');

        return Inertia::render('maintenance/Dashboard', [
            'stats' => [
                'open_count'            => $openCount + $assignedCount,
                'in_progress_count'     => $inProgressCount,
                'resolved_this_month'   => $resolvedCount + $completedCount,
                'emergency_count'       => $emergencyCount,
                'avg_resolution_hours'  => round((float) ($avgResolutionHours ?? 0), 1),
                'total_cost_this_month' => (float) $totalCostThisMonth,
            ],
            'monthly_requests'    => $this->buildMonthlyRequests(),
            'priority_distribution' => $this->buildPriorityDistribution(),
            'cost_by_category'    => $this->buildCostByCategory(),
            'recent_tickets'      => $this->buildRecentTickets(),
        ]);
    }

    private function buildMonthlyRequests(): array
    {
        return collect(range(5, 0))->map(function (int $monthsAgo) {
            $month = Carbon::now()->subMonths($monthsAgo);

            return [
                'month' => $month->format('M Y'),
                'count' => MaintenanceTicket::whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->count(),
            ];
        })->values()->all();
    }

    private function buildPriorityDistribution(): array
    {
        return collect(MaintenancePriority::cases())->map(fn ($p) => [
            'priority' => $p->value,
            'label'    => $p->label(),
            'color'    => $p->color(),
            'count'    => MaintenanceTicket::where('priority', $p)
                ->whereNotIn('status', [MaintenanceStatus::Cancelled->value])
                ->count(),
        ])->all();
    }

    private function buildCostByCategory(): array
    {
        return MaintenanceCost::join('maintenance_tickets', 'maintenance_costs.ticket_id', '=', 'maintenance_tickets.id')
            ->selectRaw('maintenance_tickets.category, SUM(maintenance_costs.amount) as total')
            ->groupBy('maintenance_tickets.category')
            ->get()
            ->map(fn ($row) => [
                'category' => $row->category,
                'total'    => (float) $row->total,
            ])
            ->all();
    }

    private function buildRecentTickets(): array
    {
        return MaintenanceTicket::with([
            'rentalTenant:id,first_name,last_name',
            'unit:id,unit_number',
            'property:id,name',
            'primaryAssignment.user:id,name',
        ])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn (MaintenanceTicket $t) => [
                'id'            => $t->id,
                'ticket_number' => $t->ticket_number,
                'title'         => $t->title,
                'priority'      => $t->priority->value,
                'priority_label' => $t->priority->label(),
                'priority_icon' => $t->priority->icon(),
                'status'        => $t->status->value,
                'status_label'  => $t->status->label(),
                'tenant_name'   => $t->rentalTenant
                    ? "{$t->rentalTenant->first_name} {$t->rentalTenant->last_name}"
                    : '—',
                'property_name' => $t->property?->name ?? '—',
                'unit_number'   => $t->unit?->unit_number ?? '—',
                'assigned_to'   => $t->primaryAssignment?->user?->name ?? '—',
                'created_at'    => $t->created_at->toDateTimeString(),
            ])
            ->all();
    }
}
