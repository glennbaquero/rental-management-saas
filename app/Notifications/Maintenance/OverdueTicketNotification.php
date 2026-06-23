<?php

declare(strict_types=1);

namespace App\Notifications\Maintenance;

use App\Models\MaintenanceTicket;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class OverdueTicketNotification extends Notification
{
    public function __construct(
        public readonly MaintenanceTicket $ticket,
        public readonly Carbon $overdueSince,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'          => 'ticket_overdue',
            'ticket_id'     => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'priority'      => $this->ticket->priority->value,
            'overdue_since' => $this->overdueSince->toDateString(),
            'message'       => "Ticket #{$this->ticket->ticket_number} is overdue since {$this->overdueSince->toFormattedDateString()}.",
        ];
    }
}
