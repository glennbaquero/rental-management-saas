<?php

declare(strict_types=1);

namespace App\Jobs\Billing;

use App\Mail\Billing\InvoiceMail;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly string $type = 'invoice',
        public readonly int $daysUntilDue = 0,
    ) {
        $this->onQueue('billing');
    }

    public function handle(): void
    {
        $invoice = $this->invoice->load(['lease.rentalTenant', 'lease.unit.property']);
        $tenant  = $invoice->lease?->rentalTenant;

        if (! $tenant?->email) {
            return;
        }

        Mail::to($tenant->email)->send(
            new InvoiceMail($invoice, $this->type, $this->daysUntilDue)
        );

        if ($this->type === 'invoice') {
            $invoice->update(['sent_at' => now()]);
        }
    }
}
