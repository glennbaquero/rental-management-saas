<?php

declare(strict_types=1);

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Trial = 'trial';
    case Active = 'active';
    case PastDue = 'past_due';
    case Canceled = 'canceled';
    case Paused = 'paused';
    case Incomplete = 'incomplete';
    case Unpaid = 'unpaid';
    case IncompleteExpired = 'incomplete_expired';

    public function label(): string
    {
        return match($this) {
            self::Trial            => 'Trial',
            self::Active           => 'Active',
            self::PastDue          => 'Past Due',
            self::Canceled         => 'Canceled',
            self::Paused           => 'Paused',
            self::Incomplete       => 'Incomplete',
            self::Unpaid           => 'Unpaid',
            self::IncompleteExpired => 'Incomplete (Expired)',
        };
    }

    public static function fromStripe(string $stripeStatus): self
    {
        return match($stripeStatus) {
            'trialing'           => self::Trial,
            'active'             => self::Active,
            'past_due'           => self::PastDue,
            'canceled'           => self::Canceled,
            'paused'             => self::Paused,
            'incomplete'         => self::Incomplete,
            'unpaid'             => self::Unpaid,
            'incomplete_expired' => self::IncompleteExpired,
            default              => self::Incomplete,
        };
    }
}
