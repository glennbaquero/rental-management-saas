<?php

declare(strict_types=1);

namespace App\Console\Commands\Billing;

use App\Enums\InvoiceStatus;
use App\Jobs\Billing\SendInvoiceReminderJob;
use App\Models\BillingSettings;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Stancl\Tenancy\Facades\Tenancy;

class SendInvoiceRemindersCommand extends Command
{
    protected $signature = 'billing:send-reminders';
    protected $description = 'Send payment reminders for upcoming and overdue invoices';

    public function handle(): int
    {
        $this->info('Sending invoice reminders...');
        $count = 0;

        Tenancy::runForMultiple(null, function ($tenant) use (&$count) {
            $settings = BillingSettings::first();

            if (! $settings || ! $settings->auto_send_reminders) {
                return;
            }

            $reminderDays = $settings->reminder_days_before ?? [7, 3, 1];
            $channels     = $settings->reminder_channels ?? ['email', 'in_app'];
            $today        = Carbon::today();

            foreach ($reminderDays as $daysBefore) {
                $targetDate = $daysBefore >= 0
                    ? $today->copy()->addDays((int) $daysBefore)
                    : $today->copy()->subDays(abs((int) $daysBefore));

                Invoice::whereIn('status', [
                    InvoiceStatus::Sent->value,
                    InvoiceStatus::Partial->value,
                    InvoiceStatus::Overdue->value,
                ])
                ->whereDate('due_date', $targetDate)
                ->with(['lease.rentalTenant'])
                ->chunk(50, function ($invoices) use ($channels, $daysBefore, &$count) {
                    foreach ($invoices as $invoice) {
                        if (! $invoice->lease?->rentalTenant) {
                            continue;
                        }

                        foreach ($channels as $channel) {
                            SendInvoiceReminderJob::dispatch($invoice, $channel, $daysBefore);
                        }

                        $count++;
                    }
                });
            }
        });

        $this->info("Done. {$count} reminder(s) dispatched.");

        return self::SUCCESS;
    }
}
