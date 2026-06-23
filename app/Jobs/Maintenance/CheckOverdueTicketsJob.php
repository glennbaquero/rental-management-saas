<?php

declare(strict_types=1);

namespace App\Jobs\Maintenance;

use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceHistory;
use App\Models\MaintenanceTicket;
use App\Models\User;
use App\Notifications\Maintenance\OverdueTicketNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckOverdueTicketsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        $activeStatuses = [
            MaintenanceStatus::Open->value,
            MaintenanceStatus::Assigned->value,
            MaintenanceStatus::InProgress->value,
            MaintenanceStatus::WaitingForParts->value,
        ];

        $overdueTickets = MaintenanceTicket::whereIn('status', $activeStatuses)
            ->whereHas('primaryAssignment', fn ($q) => $q->whereNotNull('estimated_completion')->whereDate('estimated_completion', '<', today()))
            ->with(['primaryAssignment.user', 'rentalTenant'])
            ->get();

        $managers = User::whereHas('role', fn ($q) => $q->whereIn('name', ['owner', 'property_manager']))->get();

        foreach ($overdueTickets as $ticket) {
            $assignment  = $ticket->primaryAssignment;
            $overdueSince = $assignment->estimated_completion;

            $assignment->user?->notify(new OverdueTicketNotification($ticket, $overdueSince));
            $managers->each(fn (User $u) => $u->notify(new OverdueTicketNotification($ticket, $overdueSince)));

            MaintenanceHistory::record(
                $ticket,
                'auto_escalated',
                'Ticket flagged as overdue by scheduler.',
                ['overdue_since' => $overdueSince->toDateString()],
            );
        }

        // Escalate unresolved Emergency tickets older than 24 hours
        $emergencyTickets = MaintenanceTicket::where('priority', 'emergency')
            ->whereIn('status', $activeStatuses)
            ->where('date_submitted', '<', now()->subHours(24))
            ->with('rentalTenant')
            ->get();

        foreach ($emergencyTickets as $ticket) {
            $managers->each(fn (User $u) => $u->notify(new OverdueTicketNotification($ticket, $ticket->date_submitted)));

            MaintenanceHistory::record(
                $ticket,
                'auto_escalated',
                'Emergency ticket unresolved after 24 hours — escalated.',
                ['priority' => 'emergency'],
            );
        }
    }
}
