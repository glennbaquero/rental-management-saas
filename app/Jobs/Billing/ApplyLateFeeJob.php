<?php

declare(strict_types=1);

namespace App\Jobs\Billing;

use App\Models\BillingSettings;
use App\Models\Invoice;
use App\Services\Billing\LateFeeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApplyLateFeeJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly Invoice $invoice)
    {
        $this->onQueue('billing');
    }

    public function handle(LateFeeService $service): void
    {
        $settings = BillingSettings::first();
        if (! $settings) {
            return;
        }

        $service->applyToInvoice($this->invoice, $settings);
    }
}
