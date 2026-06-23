<?php

declare(strict_types=1);

namespace App\Jobs\Maintenance;

use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceTicket;
use App\Notifications\Maintenance\TicketAssignedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendTicketAssignmentJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly MaintenanceTicket $ticket,
        public readonly MaintenanceAssignment $assignment,
    ) {
        $this->onQueue('maintenance');
    }

    public function handle(): void
    {
        $user = $this->assignment->user;

        if (! $user) {
            return;
        }

        $user->notify(new TicketAssignedNotification($this->ticket, $this->assignment));
    }
}
