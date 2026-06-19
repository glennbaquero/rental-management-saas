<?php

namespace App\Console\Commands;

use App\Models\UserInvitation;
use Illuminate\Console\Command;

class ExpireInvitations extends Command
{
    protected $signature = 'invitations:expire';

    protected $description = 'Mark all past-expiry pending invitations as expired';

    public function handle(): int
    {
        $count = UserInvitation::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Marked {$count} invitation(s) as expired.");

        return self::SUCCESS;
    }
}
