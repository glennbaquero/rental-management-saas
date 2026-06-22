<?php

declare(strict_types=1);

namespace App\Mail\Billing;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Invoice $invoice,
        public readonly string $type = 'invoice',
        public readonly int $daysUntilDue = 0,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            'reminder' => $this->daysUntilDue > 0
                ? "Payment Reminder: Invoice #{$this->invoice->invoice_number} due in {$this->daysUntilDue} day(s)"
                : "Overdue Notice: Invoice #{$this->invoice->invoice_number}",
            default    => "Invoice #{$this->invoice->invoice_number}",
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.billing.invoice',
            with: [
                'invoice'      => $this->invoice,
                'type'         => $this->type,
                'daysUntilDue' => $this->daysUntilDue,
                'tenant'       => $this->invoice->lease?->rentalTenant,
                'property'     => $this->invoice->lease?->unit?->property,
            ],
        );
    }
}
