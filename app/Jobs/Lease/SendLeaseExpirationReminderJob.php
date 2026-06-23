<?php

declare(strict_types=1);

namespace App\Jobs\Lease;

use App\Mail\Lease\LeaseExpirationReminderMail;
use App\Models\Lease;
use App\Notifications\Lease\LeaseExpirationReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendLeaseExpirationReminderJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly Lease $lease,
        public readonly int $daysRemaining,
        public readonly string $channel,
    ) {
        $this->onQueue('leases');
    }

    public function handle(): void
    {
        $lease = $this->lease->loadMissing(['rentalTenant', 'unit.property']);

        if ($this->channel === 'email') {
            $tenant = $lease->rentalTenant;
            if ($tenant?->email) {
                Mail::to($tenant->email)->send(
                    new LeaseExpirationReminderMail($lease, $this->daysRemaining)
                );
            }
        }

        if ($this->channel === 'in_app') {
            $tenant = $lease->rentalTenant;
            if ($tenant) {
                $tenant->notify(new LeaseExpirationReminderNotification($lease, $this->daysRemaining));
            }
        }
    }
}
