<?php

declare(strict_types=1);

namespace App\Console\Commands\Billing;

use App\Enums\InvoiceStatus;
use App\Jobs\Billing\ApplyLateFeeJob;
use App\Models\BillingSettings;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Stancl\Tenancy\Facades\Tenancy;

class ApplyLateFeesCommand extends Command
{
    protected $signature = 'billing:apply-late-fees';
    protected $description = 'Apply late fees to overdue invoices and mark them as overdue';

    public function handle(): int
    {
        $this->info('Checking for overdue invoices...');
        $count = 0;

        Tenancy::runForMultiple(null, function ($tenant) use (&$count) {
            $settings = BillingSettings::first();
            if (! $settings) {
                return;
            }

            $gracePeriod = $settings->grace_period_days ?? 3;

            Invoice::whereIn('status', [
                InvoiceStatus::Sent->value,
                InvoiceStatus::Partial->value,
            ])
            ->whereDate('due_date', '<', now()->subDays($gracePeriod))
            ->chunk(100, function ($invoices) use (&$count, $settings) {
                foreach ($invoices as $invoice) {
                    $invoice->update(['status' => InvoiceStatus::Overdue]);

                    if ($settings->late_fee_enabled) {
                        ApplyLateFeeJob::dispatch($invoice);
                    }

                    $count++;
                }
            });
        });

        $this->info("Done. {$count} overdue invoice(s) processed.");

        return self::SUCCESS;
    }
}
