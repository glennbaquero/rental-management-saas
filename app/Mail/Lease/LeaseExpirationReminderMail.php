<?php

declare(strict_types=1);

namespace App\Mail\Lease;

use App\Models\Lease;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaseExpirationReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Lease $lease,
        public readonly int $daysRemaining,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Lease Expiration Reminder: {$this->lease->lease_number} expires in {$this->daysRemaining} day(s)",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.lease.expiration_reminder',
            with: [
                'lease'         => $this->lease,
                'daysRemaining' => $this->daysRemaining,
                'tenant'        => $this->lease->rentalTenant,
                'unit'          => $this->lease->unit,
                'property'      => $this->lease->unit?->property,
            ],
        );
    }
}
