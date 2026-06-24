<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Enums\InvoiceStatus;
use App\Enums\LeaseStatus;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Enums\PaymentStatus;
use App\Enums\UnitStatus;
use App\Models\Invoice;
use App\Models\Lease;
use App\Models\MaintenanceTicket;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardService
{
    public function getPropertyStats(): array
    {
        $tenantId = tenant('id');

        return Cache::store('file')->remember("dashboard:props:{$tenantId}", 300, function () {
            $totalProperties = Property::count();
            $totalUnits      = Unit::count();
            $occupiedUnits   = Unit::where('status', UnitStatus::Occupied)->count();
            $vacantUnits     = Unit::where('status', UnitStatus::Available)->count();
            $occupancyRate   = $totalUnits > 0
                ? round(($occupiedUnits / $totalUnits) * 100, 1)
                : 0;

            return [
                'total_properties' => $totalProperties,
                'total_units'      => $totalUnits,
                'occupied_units'   => $occupiedUnits,
                'vacant_units'     => $vacantUnits,
                'occupancy_rate'   => $occupancyRate,
            ];
        });
    }

    public function getTenantCount(): int
    {
        return \App\Models\RentalTenant::count();
    }

    public function getRecentActivities(int $limit = 10): array
    {
        $tenantId = tenant('id');

        return Cache::store('file')->remember("dashboard:activities:{$tenantId}", 60, function () use ($limit) {
            $leases = Lease::with(['rentalTenant', 'unit.property'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(fn ($lease) => [
                    'id'          => $lease->id,
                    'type'        => 'lease',
                    'title'       => 'Lease Created',
                    'description' => "New lease for {$lease->rentalTenant?->full_name}",
                    'meta'        => [
                        'tenant'  => $lease->rentalTenant?->full_name ?? '—',
                        'unit'    => $lease->unit?->unit_number ?? '—',
                        'number'  => $lease->lease_number,
                    ],
                    'occurred_at' => $lease->created_at->toIso8601String(),
                ]);

            $payments = Payment::with(['lease.rentalTenant', 'invoice'])
                ->where('status', PaymentStatus::Completed)
                ->latest('payment_date')
                ->limit($limit)
                ->get()
                ->map(fn ($pay) => [
                    'id'          => $pay->id,
                    'type'        => 'payment',
                    'title'       => 'Payment Received',
                    'description' => "Payment from {$pay->lease?->rentalTenant?->full_name}",
                    'meta'        => [
                        'invoice' => $pay->invoice?->invoice_number ?? '—',
                        'amount'  => number_format((float) $pay->amount, 2),
                        'tenant'  => $pay->lease?->rentalTenant?->full_name ?? '—',
                    ],
                    'occurred_at' => $pay->created_at->toIso8601String(),
                ]);

            $tickets = MaintenanceTicket::with(['property', 'unit', 'rentalTenant'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(fn ($ticket) => [
                    'id'          => $ticket->id,
                    'type'        => 'maintenance',
                    'title'       => 'Maintenance Ticket Created',
                    'description' => $ticket->title,
                    'meta'        => [
                        'unit'     => $ticket->unit?->unit_number ?? '—',
                        'priority' => $ticket->priority->label(),
                        'number'   => $ticket->ticket_number,
                    ],
                    'occurred_at' => $ticket->created_at->toIso8601String(),
                ]);

            return collect($leases)
                ->concat($payments)
                ->concat($tickets)
                ->sortByDesc('occurred_at')
                ->take($limit)
                ->values()
                ->all();
        });
    }

    public function getUpcomingEvents(int $limit = 10): array
    {
        $tenantId = tenant('id');

        return Cache::store('file')->remember("dashboard:events:{$tenantId}", 120, function () use ($limit) {
            $events = collect();

            // Leases expiring in next 30 days
            $expiringLeases = Lease::with(['rentalTenant', 'unit.property'])
                ->where('status', LeaseStatus::Active)
                ->whereBetween('end_date', [Carbon::today(), Carbon::today()->addDays(30)])
                ->orderBy('end_date')
                ->limit($limit)
                ->get()
                ->map(fn ($lease) => [
                    'type'        => 'lease_expiry',
                    'title'       => "Lease Expiring: {$lease->lease_number}",
                    'description' => "{$lease->rentalTenant?->full_name} — {$lease->unit?->property?->name}",
                    'due_at'      => $lease->end_date->toDateString(),
                    'priority'    => $lease->end_date->diffInDays(Carbon::today()) <= 7 ? 'high' : 'medium',
                ]);

            // Overdue invoices
            $overdueInvoices = Invoice::with(['lease.rentalTenant'])
                ->where('status', InvoiceStatus::Overdue)
                ->orderBy('due_date')
                ->limit($limit)
                ->get()
                ->map(fn ($inv) => [
                    'type'        => 'invoice_overdue',
                    'title'       => "Overdue Invoice: {$inv->invoice_number}",
                    'description' => $inv->lease?->rentalTenant?->full_name ?? '—',
                    'due_at'      => $inv->due_date->toDateString(),
                    'priority'    => 'high',
                ]);

            // Emergency maintenance tickets
            $emergencyTickets = MaintenanceTicket::with(['property', 'unit'])
                ->where('priority', MaintenancePriority::Emergency)
                ->whereNotIn('status', [MaintenanceStatus::Resolved, MaintenanceStatus::Completed, MaintenanceStatus::Cancelled])
                ->orderBy('created_at')
                ->limit($limit)
                ->get()
                ->map(fn ($ticket) => [
                    'type'        => 'maintenance_emergency',
                    'title'       => "Emergency: {$ticket->title}",
                    'description' => "{$ticket->property?->name} — Unit {$ticket->unit?->unit_number}",
                    'due_at'      => $ticket->created_at->toDateString(),
                    'priority'    => 'emergency',
                ]);

            return $events
                ->concat($expiringLeases)
                ->concat($overdueInvoices)
                ->concat($emergencyTickets)
                ->sortBy('due_at')
                ->take($limit)
                ->values()
                ->all();
        });
    }
}
