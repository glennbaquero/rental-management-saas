<?php

declare(strict_types=1);

namespace App\Notifications\Maintenance;

use App\Models\MaintenanceTicket;
use Illuminate\Notifications\Notification;

class TicketResolvedNotification extends Notification
{
    public function __construct(public readonly MaintenanceTicket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'          => 'ticket_resolved',
            'ticket_id'     => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title'         => $this->ticket->title,
            'message'       => "Your maintenance request #{$this->ticket->ticket_number} has been completed. Please rate your experience.",
        ];
    }
}
