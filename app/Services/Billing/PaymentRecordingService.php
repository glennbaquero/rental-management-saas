<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\LedgerCategory;
use App\Enums\LedgerEntryType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentRecordingService
{
    public function record(Invoice $invoice, array $data, User $user): Payment
    {
        return DB::transaction(function () use ($invoice, $data, $user) {
            $invoice->load('lease.rentalTenant');

            $proofPath = null;
            if (! empty($data['proof_of_payment']) && $data['proof_of_payment'] instanceof \Illuminate\Http\UploadedFile) {
                $proofPath = $data['proof_of_payment']->store("payments/proofs/{$invoice->id}", 'public');
            }

            $paymentNumber = $this->generatePaymentNumber();

            $payment = Payment::create([
                'invoice_id'       => $invoice->id,
                'lease_id'         => $invoice->lease_id,
                'rental_tenant_id' => $invoice->lease->rental_tenant_id,
                'payment_number'   => $paymentNumber,
                'amount'           => $data['amount'],
                'payment_method'   => PaymentMethod::from($data['payment_method']),
                'reference_number' => $data['reference_number'] ?? null,
                'transaction_id'   => $data['transaction_id'] ?? null,
                'payment_date'     => $data['payment_date'],
                'notes'            => $data['notes'] ?? null,
                'proof_of_payment' => $proofPath,
                'received_by'      => $user->id,
                'status'           => PaymentStatus::Completed,
                'created_by'       => $user->id,
            ]);

            $this->updateInvoiceAfterPayment($invoice, (float) $data['amount']);

            LedgerEntry::create([
                'lease_id'         => $invoice->lease_id,
                'rental_tenant_id' => $invoice->lease->rental_tenant_id,
                'unit_id'          => $invoice->lease->unit_id,
                'entry_date'       => Carbon::parse($data['payment_date']),
                'type'             => LedgerEntryType::Credit,
                'category'         => LedgerCategory::Payment,
                'description'      => "Payment #{$paymentNumber} for Invoice #{$invoice->invoice_number}",
                'amount'           => $data['amount'],
                'balance_after'    => (float) $invoice->balance_due,
                'invoice_id'       => $invoice->id,
                'payment_id'       => $payment->id,
                'created_by'       => $user->id,
            ]);

            return $payment;
        });
    }

    public function updateInvoiceAfterPayment(Invoice $invoice, float $amount): void
    {
        $newPaidAmount = (float) $invoice->paid_amount + $amount;
        $newBalance    = max(0, (float) $invoice->total_amount - $newPaidAmount);

        $status = match (true) {
            $newBalance <= 0              => InvoiceStatus::Paid,
            $newPaidAmount > 0            => InvoiceStatus::Partial,
            $invoice->due_date->isPast()  => InvoiceStatus::Overdue,
            default                       => InvoiceStatus::Sent,
        };

        $invoice->update([
            'paid_amount' => $newPaidAmount,
            'balance_due' => $newBalance,
            'status'      => $status,
            'paid_at'     => $newBalance <= 0 ? now() : null,
        ]);
    }

    private function generatePaymentNumber(): string
    {
        $last = Payment::withTrashed()->whereYear('created_at', now()->year)->count();
        return 'PAY-' . now()->year . '-' . str_pad((string) ($last + 1), 5, '0', STR_PAD_LEFT);
    }
}
