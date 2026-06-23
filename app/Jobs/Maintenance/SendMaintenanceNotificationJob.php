<?php

declare(strict_types=1);

namespace App\Jobs\Maintenance;

use App\Models\MaintenanceTicket;
use App\Models\User;
use App\Notifications\Maintenance\TicketCreatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMaintenanceNotificationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly MaintenanceTicket $ticket)
    {
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        $ticket = $this->ticket->load(['rentalTenant', 'property', 'unit']);

        if ($ticket->rentalTenant) {
            $ticket->rentalTenant->notify(new TicketCreatedNotification($ticket));
        }

        User::whereHas('role', fn ($q) => $q->whereIn('name', ['owner', 'property_manager']))
            ->get()
            ->each(fn (User $user) => $user->notify(new TicketCreatedNotification($ticket)));
    }
}
