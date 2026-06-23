<?php

declare(strict_types=1);

namespace App\Notifications\Maintenance;

use App\Models\MaintenanceTicket;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification
{
    public function __construct(public readonly MaintenanceTicket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'          => 'ticket_created',
            'ticket_id'     => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'title'         => $this->ticket->title,
            'priority'      => $this->ticket->priority->value,
            'category'      => $this->ticket->category->value,
            'message'       => "New maintenance ticket #{$this->ticket->ticket_number} has been submitted: {$this->ticket->title}",
        ];
    }
}
