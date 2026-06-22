<?php

declare(strict_types=1);

namespace App\Jobs\Billing;

use App\Models\Lease;
use App\Services\Billing\InvoiceGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class GenerateInvoiceJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly Lease $lease,
        public readonly Carbon $billingDate,
    ) {
        $this->onQueue('billing');
    }

    public function handle(InvoiceGenerationService $service): void
    {
        $invoice = $service->generateForLease($this->lease, $this->billingDate);

        SendInvoiceEmailJob::dispatch($invoice);
    }
}
