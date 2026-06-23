<?php

declare(strict_types=1);

namespace App\Jobs\Lease;

use App\Enums\LeaseStatus;
use App\Models\Lease;
use App\Services\Lease\LeaseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckExpiringLeasesJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('leases');
    }

    public function handle(LeaseService $leaseService): void
    {
        $today = now()->startOfDay();
        $reminderThresholds = [90, 60, 30, 7];

        // Auto-expire leases past their end date
        Lease::query()
            ->whereIn('status', [LeaseStatus::Active->value, LeaseStatus::ExpiringSoon->value])
            ->where('end_date', '<', $today)
            ->each(function (Lease $lease) {
                AutoExpireLeaseJob::dispatch($lease)->onQueue('leases');
            });

        // Mark active leases as expiring soon (≤ 30 days)
        Lease::query()
            ->where('status', LeaseStatus::Active->value)
            ->whereBetween('end_date', [$today, $today->copy()->addDays(30)])
            ->each(function (Lease $lease) use ($leaseService) {
                $leaseService->markExpiringSoon($lease, $lease->days_remaining);
            });

        // Send expiration reminders
        foreach ($reminderThresholds as $days) {
            $targetDate = $today->copy()->addDays($days)->toDateString();

            Lease::query()
                ->whereIn('status', [LeaseStatus::Active->value, LeaseStatus::ExpiringSoon->value])
                ->whereDate('end_date', $targetDate)
                ->each(function (Lease $lease) use ($days) {
                    SendLeaseExpirationReminderJob::dispatch($lease, $days, 'email')->onQueue('leases');
                    SendLeaseExpirationReminderJob::dispatch($lease, $days, 'in_app')->onQueue('leases');
                });
        }
    }
}
