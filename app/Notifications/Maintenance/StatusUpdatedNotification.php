<?php

declare(strict_types=1);

namespace App\Notifications\Maintenance;

use App\Enums\MaintenanceStatus;
use App\Models\MaintenanceTicket;
use Illuminate\Notifications\Notification;

class StatusUpdatedNotification extends Notification
{
    public function __construct(
        public readonly MaintenanceTicket $ticket,
        public readonly MaintenanceStatus $oldStatus,
        public readonly MaintenanceStatus $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'          => 'ticket_status_updated',
            'ticket_id'     => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'old_status'    => $this->oldStatus->value,
            'new_status'    => $this->newStatus->value,
            'message'       => "Ticket #{$this->ticket->ticket_number} status changed to {$this->newStatus->label()}.",
        ];
    }
}
