<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use App\Services\Dashboard\LeaseAnalyticsService;
use App\Services\Dashboard\MaintenanceAnalyticsService;
use App\Services\Dashboard\OccupancyService;
use App\Services\Dashboard\RevenueAnalyticsService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService          $dashboard,
        private readonly RevenueAnalyticsService   $revenue,
        private readonly OccupancyService          $occupancy,
        private readonly LeaseAnalyticsService     $lease,
        private readonly MaintenanceAnalyticsService $maintenance,
    ) {}

    public function index(): Response
    {
        $user = auth()->user();
        $role = $user->role?->name ?? 'staff';

        $shared = [
            'role'               => $role,
            'recent_activities'  => $this->dashboard->getRecentActivities(),
            'upcoming_events'    => $this->dashboard->getUpcomingEvents(),
        ];

        $roleData = match ($role) {
            'owner'            => $this->ownerData(),
            'property_manager' => $this->propertyManagerData(),
            'accountant'       => $this->accountantData(),
            default            => $this->staffData($user->id),
        };

        return Inertia::render('Dashboard', array_merge($shared, $roleData));
    }

    private function ownerData(): array
    {
        $propertyStats = $this->dashboard->getPropertyStats();
        $revenueMonth  = $this->revenue->getMonthlyRevenue();
        $revenuePrev   = $this->revenue->getPreviousMonthRevenue();
        $revenueChange = $revenuePrev > 0
            ? round((($revenueMonth - $revenuePrev) / $revenuePrev) * 100, 1)
            : 0;

        return [
            'property_stats'         => $propertyStats,
            'monthly_revenue'        => $revenueMonth,
            'monthly_revenue_change' => $revenueChange,
            'outstanding_balance'    => $this->revenue->getOutstandingBalance(),
            'overdue_count'          => $this->revenue->getOverdueCount(),
            'rent_collection_rate'   => $this->revenue->getRentCollectionRate(),
            'lease_stats'            => $this->lease->getLeaseStats(),
            'revenue_trend'          => $this->revenue->getMonthlyRevenueTrend(),
            'occupancy_trend'        => $this->occupancy->getOccupancyTrend(),
            'property_occupancy'     => $this->occupancy->getPropertyOccupancyList(),
            'expiring_leases'        => $this->lease->getExpiringLeases(8),
            'maintenance_stats'      => $this->maintenance->getTicketStats(),
            'recent_tickets'         => $this->maintenance->getRecentTickets(5),
            'revenue_by_property'    => $this->revenue->getRevenueByProperty(),
            'deposits_held'          => $this->revenue->getDepositsHeld(),
        ];
    }

    private function propertyManagerData(): array
    {
        $propertyStats = $this->dashboard->getPropertyStats();

        return [
            'property_stats'     => $propertyStats,
            'tenant_count'       => $this->dashboard->getTenantCount(),
            'lease_stats'        => $this->lease->getLeaseStats(),
            'expiring_leases'    => $this->lease->getExpiringLeases(10),
            'upcoming_move_outs' => $this->lease->getUpcomingMoveOuts(),
            'maintenance_stats'  => $this->maintenance->getTicketStats(),
            'recent_tickets'     => $this->maintenance->getRecentTickets(8),
            'occupancy_stats'    => $this->occupancy->getOccupancyStats(),
            'property_occupancy' => $this->occupancy->getPropertyOccupancyList(),
        ];
    }

    private function accountantData(): array
    {
        $revenueMonth = $this->revenue->getMonthlyRevenue();
        $revenuePrev  = $this->revenue->getPreviousMonthRevenue();
        $revenueChange = $revenuePrev > 0
            ? round((($revenueMonth - $revenuePrev) / $revenuePrev) * 100, 1)
            : 0;

        return [
            'monthly_revenue'           => $revenueMonth,
            'monthly_revenue_change'    => $revenueChange,
            'outstanding_balance'       => $this->revenue->getOutstandingBalance(),
            'overdue_count'             => $this->revenue->getOverdueCount(),
            'deposits_held'             => $this->revenue->getDepositsHeld(),
            'rent_collection_rate'      => $this->revenue->getRentCollectionRate(),
            'revenue_trend'             => $this->revenue->getMonthlyRevenueTrend(),
            'payment_method_breakdown'  => $this->revenue->getPaymentMethodBreakdown(),
            'revenue_by_property'       => $this->revenue->getRevenueByProperty(),
            'recent_invoices'           => $this->revenue->getRecentInvoices(10),
        ];
    }

    private function staffData(string $userId): array
    {
        $allStats = $this->maintenance->getTicketStats();

        return [
            'maintenance_stats'    => $allStats,
            'assigned_tickets'     => $this->maintenance->getAssignedTickets($userId, 10),
            'monthly_trend'        => $this->maintenance->getMonthlyRequestTrend(),
            'priority_distribution' => $this->maintenance->getPriorityDistribution(),
        ];
    }
}
