<?php

namespace Database\Seeders;

use App\Enums\BillingCycle;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\LateFeeType;
use App\Enums\LeaseStatus;
use App\Enums\LedgerCategory;
use App\Enums\LedgerEntryType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UnitStatus;
use App\Models\BillingSettings;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\LateFee;
use App\Models\Lease;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\Property;
use App\Models\RentalTenant;
use App\Models\Unit;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BillingSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedBillingSettings();
        $this->seedLeasesAndBilling();
    }

    // -------------------------------------------------------------------------
    // Billing Settings
    // -------------------------------------------------------------------------

    private function seedBillingSettings(): void
    {
        BillingSettings::updateOrCreate([], [
            'currency'                  => 'PHP',
            'timezone'                  => 'Asia/Manila',
            'invoice_prefix'            => 'INV',
            'invoice_number_format'     => '{PREFIX}-{YEAR}-{SEQ5}',
            'grace_period_days'         => 3,
            'auto_generate_invoices'    => true,
            'auto_send_reminders'       => true,
            'late_fee_enabled'          => true,
            'late_fee_type'             => LateFeeType::Fixed->value,
            'late_fee_amount'           => 500.00,
            'late_fee_percentage'       => 5.00,
            'apply_late_fee_after_days' => 1,
            'compound_monthly'          => false,
            'reminder_days_before'      => [7, 3, 1, -1, -7],
            'reminder_channels'         => ['email', 'in_app'],
        ]);

        $this->command->info('  ✓ Billing settings seeded.');
    }

    // -------------------------------------------------------------------------
    // Leases + Invoices + Payments + Late Fees
    // -------------------------------------------------------------------------

    private function seedLeasesAndBilling(): void
    {
        $admin   = User::first();
        $tenants = RentalTenant::all();

        if ($tenants->isEmpty()) {
            $this->command->warn('  ! No tenants found. Run RentalTenantSeeder first.');
            return;
        }

        $units = $this->ensureUnits($admin);

        // Pair up tenants with units (up to 3 leases)
        $pairs = collect([
            ['tenant' => $tenants->get(0), 'unit' => $units->get(0)],
            ['tenant' => $tenants->get(1), 'unit' => $units->get(1)],
            ['tenant' => $tenants->get(2), 'unit' => $units->get(2)],
        ])->filter(fn ($p) => $p['tenant'] && $p['unit']);

        $seq = 1;
        foreach ($pairs as $pair) {
            $this->createLeaseWithHistory($pair['tenant'], $pair['unit'], $admin, $seq);
            $seq++;
        }

        $this->command->info('  ✓ Leases, invoices, payments, and late fees seeded.');
    }

    private function ensureUnits(User $admin): \Illuminate\Support\Collection
    {
        $property = Property::first();
        $unitType = UnitType::first();

        if (! $property || ! $unitType) {
            $this->command->warn('  ! No property or unit type found. Cannot create units.');
            return collect();
        }

        $needed = [
            ['unit_number' => '101', 'floor' => 1, 'bedrooms' => 1, 'bathrooms' => 1, 'area_sqm' => 35, 'rent_amount' => 15000],
            ['unit_number' => '102', 'floor' => 1, 'bedrooms' => 2, 'bathrooms' => 1, 'area_sqm' => 55, 'rent_amount' => 18500],
            ['unit_number' => '201', 'floor' => 2, 'bedrooms' => 1, 'bathrooms' => 1, 'area_sqm' => 35, 'rent_amount' => 12000],
        ];

        foreach ($needed as $data) {
            Unit::firstOrCreate(
                ['property_id' => $property->id, 'unit_number' => $data['unit_number']],
                [
                    'property_id'    => $property->id,
                    'unit_type_id'   => $unitType->id,
                    'unit_number'    => $data['unit_number'],
                    'floor'          => $data['floor'],
                    'bedrooms'       => $data['bedrooms'],
                    'bathrooms'      => $data['bathrooms'],
                    'area_sqm'       => $data['area_sqm'],
                    'rent_amount'    => $data['rent_amount'],
                    'deposit_amount' => $data['rent_amount'],
                    'status'         => UnitStatus::Available,
                ]
            );
        }

        return Unit::where('property_id', $property->id)
            ->whereIn('unit_number', array_column($needed, 'unit_number'))
            ->get();
    }

    private function createLeaseWithHistory(
        RentalTenant $tenant,
        Unit $unit,
        User $admin,
        int $seq
    ): void {
        $scenarios = [
            1 => 'active_good_standing',   // Active lease, all invoices paid
            2 => 'active_partial_payment', // Active lease, latest invoice partially paid
            3 => 'active_overdue',         // Active lease, has an overdue invoice + late fee
        ];

        $scenario = $scenarios[$seq] ?? 'active_good_standing';

        $leaseStart  = Carbon::now()->subMonths(5)->startOfMonth();
        $leaseEnd    = Carbon::now()->addMonths(7)->endOfMonth();
        $rentAmount  = match ($seq) {
            1 => 15000.00,
            2 => 18500.00,
            3 => 12000.00,
            default => 15000.00,
        };

        $leaseNumber = 'LSE-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);

        // Skip if this lease number already exists (idempotent)
        if (Lease::where('lease_number', $leaseNumber)->exists()) {
            $this->command->warn("  ! Lease {$leaseNumber} already exists. Skipping.");
            return;
        }

        $lease = Lease::create([
            'unit_id'              => $unit->id,
            'rental_tenant_id'     => $tenant->id,
            'lease_number'         => $leaseNumber,
            'start_date'           => $leaseStart,
            'end_date'             => $leaseEnd,
            'rent_amount'          => $rentAmount,
            'deposit_amount'       => $rentAmount,
            'deposit_paid'         => true,
            'deposit_paid_date'    => $leaseStart,
            'billing_day'          => 5,
            'billing_cycle'        => BillingCycle::Monthly,
            'generate_days_before' => 5,
            'issue_date_offset'    => 0,
            'status'               => LeaseStatus::Active,
            'notes'                => "Seeded lease #{$seq} for testing.",
            'created_by'           => $admin->id,
        ]);

        // Update unit status to occupied
        $unit->update(['status' => 'occupied']);
        $tenant->update(['status' => 'active']);

        // Generate 5 months of past invoices
        $this->generatePastInvoices($lease, $admin, $seq, $scenario);
    }

    private function nextInvoiceSeq(): int
    {
        static $seq = null;
        if ($seq === null) {
            $last = Invoice::orderByDesc('invoice_number')->value('invoice_number');
            $seq  = $last ? ((int) substr($last, -5)) + 1 : 1;
        }
        return $seq++;
    }

    private function generatePastInvoices(
        Lease $lease,
        User $admin,
        int $seq,
        string $scenario
    ): void {

        // Months: 5 months ago → 1 month ago (fully paid)
        // Current month invoice: status depends on scenario
        for ($monthsAgo = 5; $monthsAgo >= 0; $monthsAgo--) {
            $billingDate = Carbon::now()->subMonths($monthsAgo)->startOfMonth();
            $dueDate     = $billingDate->copy()->addMonth()->setDay(5);
            $issueDate   = $dueDate->copy()->subDays(5);
            $periodStart = $billingDate->copy();
            $periodEnd   = $billingDate->copy()->endOfMonth();

            $invoiceNumber = 'INV-' . now()->year . '-' . str_pad((string) $this->nextInvoiceSeq(), 5, '0', STR_PAD_LEFT);

            $isCurrentMonth = $monthsAgo === 0;

            [$status, $paidAmount] = $this->resolveInvoiceStatus(
                $scenario,
                $isCurrentMonth,
                $dueDate,
                (float) $lease->rent_amount
            );

            $balance = (float) $lease->rent_amount - $paidAmount;

            $invoice = Invoice::create([
                'lease_id'             => $lease->id,
                'invoice_number'       => $invoiceNumber,
                'type'                 => InvoiceType::Rent,
                'status'               => $status,
                'billing_period_start' => $periodStart,
                'billing_period_end'   => $periodEnd,
                'issue_date'           => $issueDate,
                'due_date'             => $dueDate,
                'subtotal'             => $lease->rent_amount,
                'tax_amount'           => 0,
                'late_fee_amount'      => 0,
                'discount_amount'      => 0,
                'total_amount'         => $lease->rent_amount,
                'paid_amount'          => $paidAmount,
                'balance_due'          => max(0, $balance),
                'notes'                => null,
                'sent_at'              => $issueDate,
                'paid_at'              => $status === InvoiceStatus::Paid ? $dueDate : null,
                'voided_at'            => null,
                'created_by'           => $admin->id,
            ]);

            InvoiceItem::create([
                'invoice_id'  => $invoice->id,
                'description' => 'Monthly Rent — ' . $periodStart->format('M d') . ' to ' . $periodEnd->format('M d, Y'),
                'quantity'    => 1,
                'unit_price'  => $lease->rent_amount,
                'total_price' => $lease->rent_amount,
                'type'        => 'rent',
            ]);

            // Record payments for non-zero paid amounts
            if ($paidAmount > 0) {
                $this->recordPayment($invoice, $lease, $admin, $paidAmount, $dueDate);
            }

            // Add partial second payment for partial scenario on current month
            if ($isCurrentMonth && $scenario === 'active_partial_payment') {
                $firstPayment = round((float) $lease->rent_amount * 0.4, 2);
                $invoice->update([
                    'paid_amount' => $firstPayment,
                    'balance_due' => (float) $lease->rent_amount - $firstPayment,
                    'status'      => InvoiceStatus::Partial,
                    'paid_at'     => null,
                ]);
                $this->recordPayment($invoice, $lease, $admin, $firstPayment, $dueDate, 'gcash');
            }

            // Add late fee for overdue scenario on current month
            if ($isCurrentMonth && $scenario === 'active_overdue') {
                $this->applyLateFee($invoice, $lease, $admin);
            }

            // Ledger entry for paid invoices
            if ($paidAmount > 0) {
                $balanceAfter = max(0, (float) $invoice->balance_due);
                LedgerEntry::create([
                    'lease_id'         => $lease->id,
                    'rental_tenant_id' => $lease->rental_tenant_id,
                    'unit_id'          => $lease->unit_id,
                    'entry_date'       => $dueDate,
                    'type'             => LedgerEntryType::Credit,
                    'category'         => LedgerCategory::Rent,
                    'description'      => "Rent payment for {$periodStart->format('M Y')}",
                    'amount'           => $paidAmount,
                    'balance_after'    => $balanceAfter,
                    'invoice_id'       => $invoice->id,
                    'created_by'       => $admin->id,
                ]);
            }
        }
    }

    private function resolveInvoiceStatus(
        string $scenario,
        bool $isCurrentMonth,
        Carbon $dueDate,
        float $rentAmount
    ): array {
        // Past months: always fully paid
        if (! $isCurrentMonth) {
            return [InvoiceStatus::Paid, $rentAmount];
        }

        // Current month depends on scenario
        return match ($scenario) {
            'active_good_standing'   => [InvoiceStatus::Paid, $rentAmount],
            'active_partial_payment' => [InvoiceStatus::Partial, 0],   // will be set after
            'active_overdue'         => [InvoiceStatus::Overdue, 0],
            default                  => [InvoiceStatus::Sent, 0],
        };
    }

    private function nextPaymentSeq(): int
    {
        static $seq = null;
        if ($seq === null) {
            $last = Payment::orderByDesc('payment_number')->value('payment_number');
            $seq  = $last ? ((int) substr($last, -5)) + 1 : 1;
        }
        return $seq++;
    }

    private function recordPayment(
        Invoice $invoice,
        Lease $lease,
        User $admin,
        float $amount,
        Carbon $paymentDate,
        string $method = 'bank_transfer'
    ): Payment {
        $n = $this->nextPaymentSeq();

        return Payment::create([
            'invoice_id'       => $invoice->id,
            'lease_id'         => $lease->id,
            'rental_tenant_id' => $lease->rental_tenant_id,
            'payment_number'   => 'PAY-' . now()->year . '-' . str_pad((string) $n, 5, '0', STR_PAD_LEFT),
            'amount'           => $amount,
            'payment_method'   => PaymentMethod::from($method),
            'reference_number' => 'REF-' . strtoupper(substr(md5((string) $n), 0, 8)),
            'payment_date'     => $paymentDate,
            'notes'            => null,
            'received_by'      => $admin->id,
            'status'           => PaymentStatus::Completed,
            'created_by'       => $admin->id,
        ]);
    }

    private function applyLateFee(Invoice $invoice, Lease $lease, User $admin): void
    {
        $daysOverdue = 10;
        $feeAmount   = 500.00;

        LateFee::create([
            'invoice_id'  => $invoice->id,
            'lease_id'    => $lease->id,
            'type'        => LateFeeType::Fixed,
            'rate'        => 500.00,
            'amount'      => $feeAmount,
            'days_overdue' => $daysOverdue,
            'applied_at'  => now(),
            'created_by'  => $admin->id,
        ]);

        $invoice->increment('late_fee_amount', $feeAmount);
        $invoice->increment('total_amount', $feeAmount);
        $invoice->increment('balance_due', $feeAmount);
    }
}
