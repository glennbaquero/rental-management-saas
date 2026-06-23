<?php

declare(strict_types=1);

namespace App\Jobs\Lease;

use App\Models\Lease;
use App\Services\Lease\LeaseService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoExpireLeaseJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly Lease $lease,
    ) {
        $this->onQueue('leases');
    }

    public function handle(LeaseService $leaseService): void
    {
        $leaseService->expire($this->lease);
    }
}
