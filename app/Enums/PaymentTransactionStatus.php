<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentTransactionStatus: string
{
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Canceled = 'canceled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::Succeeded => 'Succeeded',
            self::Failed    => 'Failed',
            self::Canceled  => 'Canceled',
            self::Refunded  => 'Refunded',
        };
    }
}
