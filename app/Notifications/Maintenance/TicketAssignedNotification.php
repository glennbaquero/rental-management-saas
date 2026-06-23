<?php

declare(strict_types=1);

namespace App\Notifications\Maintenance;

use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceTicket;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification
{
    public function __construct(
        public readonly MaintenanceTicket $ticket,
        public readonly MaintenanceAssignment $assignment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'                => 'ticket_assigned',
            'ticket_id'           => $this->ticket->id,
            'ticket_number'       => $this->ticket->ticket_number,
            'title'               => $this->ticket->title,
            'assignee_type'       => $this->assignment->assignee_type->value,
            'estimated_completion' => $this->assignment->estimated_completion?->toDateString(),
            'message'             => "You have been assigned to maintenance ticket #{$this->ticket->ticket_number}: {$this->ticket->title}",
        ];
    }
}
