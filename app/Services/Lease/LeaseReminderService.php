<?php

declare(strict_types=1);

namespace App\Services\Lease;

use App\Jobs\Lease\SendLeaseExpirationReminderJob;
use App\Models\Lease;

class LeaseReminderService
{
    public function sendExpirationReminder(Lease $lease, int $daysRemaining): void
    {
        SendLeaseExpirationReminderJob::dispatch($lease, $daysRemaining, 'email');
        SendLeaseExpirationReminderJob::dispatch($lease, $daysRemaining, 'in_app');
    }
}
