<?php

declare(strict_types=1);

namespace App\Events\Stripe;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebhookProcessed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $stripeEventId,
        public readonly string $eventType,
        public readonly ?string $tenantId,
        public readonly float $executionTimeMs,
    ) {}
}
