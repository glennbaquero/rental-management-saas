<?php

declare(strict_types=1);

namespace App\Enums;

enum WebhookEventStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';

    public function label(): string
    {
        return match($this) {
            self::Pending    => 'Pending',
            self::Processing => 'Processing',
            self::Processed  => 'Processed',
            self::Failed     => 'Failed',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending    => 'yellow',
            self::Processing => 'blue',
            self::Processed  => 'green',
            self::Failed     => 'red',
        };
    }
}
