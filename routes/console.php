<?php

use App\Console\Commands\ExpireInvitations;
use App\Jobs\Lease\CheckExpiringLeasesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command(ExpireInvitations::class)->weekly();

Schedule::job(new CheckExpiringLeasesJob)->daily()->at('08:00');
