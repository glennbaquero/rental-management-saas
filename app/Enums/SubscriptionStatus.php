<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';

    public function label(): string
    {
        return match($this) {
            self::Trial => 'Trial',
            self::Active => 'Active',
            self::PastDue => 'Past Due',
            self::Canceled => 'Canceled',
        };
    }
}
