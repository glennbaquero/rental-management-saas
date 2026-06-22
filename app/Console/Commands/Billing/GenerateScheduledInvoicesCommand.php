<?php

declare(strict_types=1);

namespace App\Console\Commands\Billing;

use App\Jobs\Billing\GenerateInvoiceJob;
use App\Models\BillingSettings;
use App\Models\Lease;
use App\Services\Billing\InvoiceGenerationService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Facades\Tenancy;

class GenerateScheduledInvoicesCommand extends Command
{
    protected $signature = 'billing:generate-invoices {--dry-run : Preview without creating invoices}';
    protected $description = 'Auto-generate invoices for active leases based on billing schedule';

    public function handle(InvoiceGenerationService $service): int
    {
        $today   = Carbon::today();
        $isDryRun = (bool) $this->option('dry-run');
        $count   = 0;

        $this->info("Checking active leases for invoice generation on {$today->toDateString()}...");

        Tenancy::runForMultiple(null, function ($tenant) use ($service, $today, $isDryRun, &$count) {
            $settings = BillingSettings::first();

            if ($settings && ! $settings->auto_generate_invoices) {
                return;
            }

            Lease::where('status', 'active')
                ->with(['unit', 'rentalTenant'])
                ->chunk(100, function ($leases) use ($service, $today, $isDryRun, &$count) {
                    foreach ($leases as $lease) {
                        if (! $service->shouldGenerateToday($lease, $today)) {
                            continue;
                        }

                        $count++;

                        if ($isDryRun) {
                            $this->line("  [DRY RUN] Would generate invoice for lease #{$lease->lease_number}");
                            continue;
                        }

                        GenerateInvoiceJob::dispatch($lease, $today);
                        $this->line("  Dispatched invoice generation for lease #{$lease->lease_number}");
                    }
                });
        });

        $this->info($isDryRun
            ? "Dry run complete. {$count} invoice(s) would be generated."
            : "Done. {$count} invoice generation job(s) dispatched."
        );

        return self::SUCCESS;
    }
}
