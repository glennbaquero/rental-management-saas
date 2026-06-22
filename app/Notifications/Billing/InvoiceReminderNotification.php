<?php

declare(strict_types=1);

namespace App\Notifications\Billing;

use App\Models\Invoice;
use Illuminate\Notifications\Notification;

class InvoiceReminderNotification extends Notification
{
    public function __construct(
        public readonly Invoice $invoice,
        public readonly int $daysUntilDue,
        public readonly string $message,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'           => 'invoice_reminder',
            'invoice_id'     => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'due_date'       => $this->invoice->due_date->toDateString(),
            'amount'         => $this->invoice->balance_due,
            'message'        => $this->message,
            'days_until_due' => $this->daysUntilDue,
        ];
    }
}
