<?php

declare(strict_types=1);

namespace App\Jobs\Maintenance;

use App\Models\MaintenanceTicket;
use App\Notifications\Maintenance\TicketResolvedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendResolutionNotificationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly MaintenanceTicket $ticket)
    {
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        $ticket = $this->ticket->load('rentalTenant');

        if ($ticket->rentalTenant) {
            $ticket->rentalTenant->notify(new TicketResolvedNotification($ticket));
        }
    }
}
