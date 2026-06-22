<?php

declare(strict_types=1);

namespace App\Jobs\Billing;

use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendInvoiceReminderJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly string $channel,
        public readonly int $daysUntilDue,
    ) {
        $this->onQueue('billing');
    }

    public function handle(): void
    {
        $invoice = $this->invoice->load(['lease.rentalTenant', 'lease.unit.property']);
        $tenant  = $invoice->lease?->rentalTenant;

        if (! $tenant) {
            return;
        }

        match ($this->channel) {
            'email'  => $this->sendEmail($invoice, $tenant),
            'in_app' => $this->sendInAppNotification($invoice, $tenant),
            default  => Log::warning("Unknown reminder channel: {$this->channel}"),
        };
    }

    private function sendEmail(Invoice $invoice, $tenant): void
    {
        // Dispatch dedicated email job for queued mail handling
        SendInvoiceEmailJob::dispatch($invoice, 'reminder', $this->daysUntilDue);
    }

    private function sendInAppNotification(Invoice $invoice, $tenant): void
    {
        $message = $this->daysUntilDue > 0
            ? "Invoice #{$invoice->invoice_number} is due in {$this->daysUntilDue} day(s)."
            : "Invoice #{$invoice->invoice_number} is overdue. Please settle your balance.";

        $tenant->notify(new \App\Notifications\Billing\InvoiceReminderNotification(
            invoice: $invoice,
            daysUntilDue: $this->daysUntilDue,
            message: $message,
        ));
    }
}
