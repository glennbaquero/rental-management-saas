<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Property;
use App\Services\Billing\PaymentRecordingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $payments = Payment::query()
            ->with(['invoice', 'lease.rentalTenant', 'verifiedBy'])
            ->when($request->search, function ($query, $value) {
                $query->where('payment_number', 'like', "%{$value}%")
                    ->orWhere('reference_number', 'like', "%{$value}%")
                    ->orWhereHas('lease.rentalTenant', fn ($q) =>
                        $q->where('first_name', 'like', "%{$value}%")
                          ->orWhere('last_name', 'like', "%{$value}%")
                    );
            })
            ->when($request->method, fn ($q, $v) => $q->where('payment_method', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('payment_date', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->whereDate('payment_date', '<=', $v))
            ->latest('payment_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Payment $payment) => $this->transformPayment($payment));

        return Inertia::render('billing/payments/Index', [
            'payments' => $payments,
            'filters'  => $request->only(['search', 'method', 'status', 'date_from', 'date_to']),
            'methods'  => collect(PaymentMethod::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'statuses' => collect(PaymentStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        $data = $request->validate([
            'amount'           => 'required|numeric|min:0.01',
            'payment_method'   => 'required|in:' . implode(',', array_column(PaymentMethod::cases(), 'value')),
            'payment_date'     => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'transaction_id'   => 'nullable|string|max:100',
            'notes'            => 'nullable|string|max:1000',
            'proof_of_payment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ((float) $data['amount'] <= 0) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Payment amount must be greater than zero.']);
        }

        $payment = app(PaymentRecordingService::class)->record($invoice, $data, $request->user());

        return back()->with('toast', ['type' => 'success', 'message' => "Payment #{$payment->payment_number} recorded successfully."]);
    }

    public function verify(Request $request, Payment $payment): RedirectResponse
    {
        if ($payment->status !== PaymentStatus::Pending) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Only pending payments can be verified.']);
        }

        $payment->update([
            'status'      => PaymentStatus::Completed,
            'verified_at' => now(),
            'verified_by' => $request->user()->id,
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Payment verified.']);
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $payment->update([
            'status'           => PaymentStatus::Failed,
            'rejection_reason' => $request->rejection_reason,
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Payment rejected.']);
    }

    public function uploadProof(Request $request, Payment $payment): RedirectResponse
    {
        $request->validate([
            'proof_of_payment' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($payment->proof_of_payment) {
            Storage::disk('public')->delete($payment->proof_of_payment);
        }

        $path = $request->file('proof_of_payment')->store("payments/proofs/{$payment->id}", 'public');

        $payment->update(['proof_of_payment' => $path]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Proof of payment uploaded.']);
    }

    private function transformPayment(Payment $payment): array
    {
        $tenant = $payment->lease?->rentalTenant;

        return [
            'id'                   => $payment->id,
            'payment_number'       => $payment->payment_number,
            'invoice_id'           => $payment->invoice_id,
            'invoice_number'       => $payment->invoice?->invoice_number,
            'tenant_name'          => $tenant?->full_name ?? '—',
            'tenant_id'            => $tenant?->id,
            'amount'               => (float) $payment->amount,
            'payment_method'       => $payment->payment_method->value,
            'method_label'         => $payment->payment_method->label(),
            'reference_number'     => $payment->reference_number,
            'transaction_id'       => $payment->transaction_id,
            'payment_date'         => $payment->payment_date->toDateString(),
            'notes'                => $payment->notes,
            'status'               => $payment->status->value,
            'status_label'         => $payment->status->label(),
            'proof_of_payment_url' => $payment->proof_of_payment_url,
            'verified_at'          => $payment->verified_at?->toISOString(),
            'verified_by_name'     => $payment->verifiedBy?->name,
            'rejection_reason'     => $payment->rejection_reason,
        ];
    }
}
