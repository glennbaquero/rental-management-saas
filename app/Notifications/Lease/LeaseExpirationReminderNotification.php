<?php

declare(strict_types=1);

namespace App\Notifications\Lease;

use App\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeaseExpirationReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Lease $lease,
        public readonly int $daysRemaining,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'          => 'lease_expiration_reminder',
            'lease_id'      => $this->lease->id,
            'lease_number'  => $this->lease->lease_number,
            'tenant_name'   => $this->lease->rentalTenant?->full_name,
            'end_date'      => $this->lease->end_date?->toDateString(),
            'days_remaining' => $this->daysRemaining,
            'message'       => "Lease {$this->lease->lease_number} expires in {$this->daysRemaining} day(s).",
        ];
    }
}
