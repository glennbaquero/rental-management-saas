<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Jobs\Billing\SendInvoiceEmailJob;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Lease;
use App\Models\Payment;
use App\Models\Property;
use App\Services\Billing\InvoiceGenerationService;
use App\Services\Billing\PaymentRecordingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $invoices = Invoice::query()
            ->with(['lease.rentalTenant', 'lease.unit.property', 'lease.unit.building'])
            ->when($request->search, function ($query, $value) {
                $query->where('invoice_number', 'like', "%{$value}%")
                    ->orWhereHas('lease.rentalTenant', fn ($q) =>
                        $q->where('first_name', 'like', "%{$value}%")
                          ->orWhere('last_name', 'like', "%{$value}%")
                          ->orWhere('email', 'like', "%{$value}%")
                    );
            })
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->property_id, fn ($q, $v) =>
                $q->whereHas('lease.unit', fn ($uq) => $uq->where('property_id', $v))
            )
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('due_date', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->whereDate('due_date', '<=', $v))
            ->latest('due_date')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Invoice $invoice) => $this->transformInvoiceRow($invoice));

        $properties = Property::orderBy('name')->get(['id', 'name'])
            ->map(fn ($p) => ['value' => $p->id, 'label' => $p->name]);

        return Inertia::render('billing/invoices/Index', [
            'invoices'   => $invoices,
            'filters'    => $request->only(['search', 'status', 'property_id', 'date_from', 'date_to']),
            'statuses'   => collect(InvoiceStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'properties' => $properties,
        ]);
    }

    public function create(): Response
    {
        $leases = Lease::where('status', 'active')
            ->with(['rentalTenant', 'unit.property'])
            ->get()
            ->map(fn ($l) => [
                'value'       => $l->id,
                'label'       => "{$l->rentalTenant?->full_name} — {$l->unit?->property?->name} Unit {$l->unit?->unit_number}",
                'rent_amount' => (float) $l->rent_amount,
                'billing_day' => $l->billing_day,
            ]);

        return Inertia::render('billing/invoices/Create', [
            'leases'       => $leases,
            'invoiceTypes' => collect(InvoiceType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'statuses'     => collect(InvoiceStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'lease_id'             => 'required|uuid|exists:leases,id',
            'type'                 => 'required|in:' . implode(',', array_column(InvoiceType::cases(), 'value')),
            'issue_date'           => 'nullable|date',
            'due_date'             => 'required|date',
            'billing_period_start' => 'nullable|date',
            'billing_period_end'   => 'nullable|date',
            'notes'                => 'nullable|string|max:1000',
            'items'                => 'required|array|min:1',
            'items.*.description'  => 'required|string',
            'items.*.quantity'     => 'required|numeric|min:0.01',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.type'         => 'nullable|string',
            'discount_amount'      => 'nullable|numeric|min:0',
            'tax_amount'           => 'nullable|numeric|min:0',
        ]);

        $service = app(InvoiceGenerationService::class);
        $settings = \App\Models\BillingSettings::first() ?? new \App\Models\BillingSettings;

        DB::transaction(function () use ($data, $service, $settings) {
            $subtotal  = collect($data['items'])->sum(fn ($i) => $i['quantity'] * $i['unit_price']);
            $discount  = (float) ($data['discount_amount'] ?? 0);
            $tax       = (float) ($data['tax_amount'] ?? 0);
            $total     = $subtotal - $discount + $tax;

            $invoice = Invoice::create([
                'lease_id'             => $data['lease_id'],
                'invoice_number'       => $service->buildInvoiceNumber($settings),
                'type'                 => $data['type'],
                'status'               => InvoiceStatus::Draft,
                'billing_period_start' => $data['billing_period_start'] ?? null,
                'billing_period_end'   => $data['billing_period_end'] ?? null,
                'issue_date'           => $data['issue_date'] ?? today(),
                'due_date'             => $data['due_date'],
                'subtotal'             => $subtotal,
                'tax_amount'           => $tax,
                'late_fee_amount'      => 0,
                'discount_amount'      => $discount,
                'total_amount'         => $total,
                'paid_amount'          => 0,
                'balance_due'          => $total,
                'notes'                => $data['notes'] ?? null,
                'created_by'           => $request->user()->id,
            ]);

            foreach ($data['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'description' => $item['description'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'total_price' => $item['quantity'] * $item['unit_price'],
                    'type'        => $item['type'] ?? null,
                ]);
            }

            return $invoice;
        });

        return redirect()->route('billing.invoices.index')
            ->with('toast', ['type' => 'success', 'message' => 'Invoice created successfully.']);
    }

    public function show(Invoice $invoice): Response
    {
        $invoice->load([
            'lease.rentalTenant',
            'lease.unit.property',
            'lease.unit.building',
            'items',
            'payments.createdBy',
            'payments.verifiedBy',
            'lateFees',
        ]);

        return Inertia::render('billing/invoices/Show', [
            'invoice'        => $this->transformInvoice($invoice),
            'paymentMethods' => collect(PaymentMethod::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        if (in_array($invoice->status, [InvoiceStatus::Paid])) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Cannot void a paid invoice.']);
        }

        $invoice->update([
            'status'    => InvoiceStatus::Void,
            'voided_at' => now(),
        ]);

        return redirect()->route('billing.invoices.index')
            ->with('toast', ['type' => 'success', 'message' => 'Invoice voided.']);
    }

    public function send(Invoice $invoice): RedirectResponse
    {
        if ($invoice->status === InvoiceStatus::Void) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Cannot send a voided invoice.']);
        }

        $invoice->update([
            'status'  => InvoiceStatus::Sent,
            'sent_at' => now(),
        ]);

        SendInvoiceEmailJob::dispatch($invoice);

        return back()->with('toast', ['type' => 'success', 'message' => 'Invoice sent.']);
    }

    private function transformInvoiceRow(Invoice $invoice): array
    {
        $lease    = $invoice->lease;
        $tenant   = $lease?->rentalTenant;
        $unit     = $lease?->unit;
        $property = $unit?->property;
        $building = $unit?->building;

        return [
            'id'             => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'type'           => $invoice->type->value,
            'type_label'     => $invoice->type->label(),
            'status'         => $invoice->status->value,
            'status_label'   => $invoice->status->label(),
            'issue_date'     => $invoice->issue_date?->toDateString(),
            'due_date'       => $invoice->due_date->toDateString(),
            'total_amount'   => (float) $invoice->total_amount,
            'paid_amount'    => (float) $invoice->paid_amount,
            'balance_due'    => (float) $invoice->balance_due,
            'tenant_name'    => $tenant?->full_name ?? '—',
            'tenant_id'      => $tenant?->id,
            'property_name'  => $property?->name ?? '—',
            'property_id'    => $property?->id,
            'unit_number'    => $unit?->unit_number ?? '—',
            'building_name'  => $building?->name,
            'sent_at'        => $invoice->sent_at?->toISOString(),
        ];
    }

    private function transformInvoice(Invoice $invoice): array
    {
        $row      = $this->transformInvoiceRow($invoice);
        $lease    = $invoice->lease;
        $tenant   = $lease?->rentalTenant;

        return array_merge($row, [
            'billing_period_start' => $invoice->billing_period_start?->toDateString(),
            'billing_period_end'   => $invoice->billing_period_end?->toDateString(),
            'subtotal'             => (float) $invoice->subtotal,
            'tax_amount'           => (float) $invoice->tax_amount,
            'late_fee_amount'      => (float) $invoice->late_fee_amount,
            'discount_amount'      => (float) $invoice->discount_amount,
            'notes'                => $invoice->notes,
            'paid_at'              => $invoice->paid_at?->toISOString(),
            'voided_at'            => $invoice->voided_at?->toISOString(),
            'tenant' => $tenant ? [
                'id'          => $tenant->id,
                'full_name'   => $tenant->full_name,
                'email'       => $tenant->email,
                'phone'       => $tenant->phone,
                'tenant_code' => $tenant->tenant_code,
            ] : null,
            'lease' => $lease ? [
                'id'           => $lease->id,
                'lease_number' => $lease->lease_number,
                'rent_amount'  => (float) $lease->rent_amount,
                'billing_day'  => $lease->billing_day,
            ] : null,
            'items' => $invoice->items->map(fn ($item) => [
                'id'          => $item->id,
                'description' => $item->description,
                'quantity'    => (float) $item->quantity,
                'unit_price'  => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
                'type'        => $item->type,
            ])->values()->all(),
            'payments' => $invoice->payments->map(fn ($p) => [
                'id'                   => $p->id,
                'payment_number'       => $p->payment_number,
                'amount'               => (float) $p->amount,
                'payment_method'       => $p->payment_method->value,
                'method_label'         => $p->payment_method->label(),
                'reference_number'     => $p->reference_number,
                'transaction_id'       => $p->transaction_id,
                'payment_date'         => $p->payment_date->toDateString(),
                'notes'                => $p->notes,
                'status'               => $p->status->value,
                'status_label'         => $p->status->label(),
                'proof_of_payment_url' => $p->proof_of_payment_url,
                'verified_at'          => $p->verified_at?->toISOString(),
                'created_by_name'      => $p->createdBy?->name,
            ])->values()->all(),
            'late_fees' => $invoice->lateFees->map(fn ($lf) => [
                'id'          => $lf->id,
                'type'        => $lf->type->value,
                'type_label'  => $lf->type->label(),
                'rate'        => (float) $lf->rate,
                'amount'      => (float) $lf->amount,
                'days_overdue' => $lf->days_overdue,
                'applied_at'  => $lf->applied_at->toISOString(),
            ])->values()->all(),
        ]);
    }
}
