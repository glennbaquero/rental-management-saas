<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lease;

use App\Enums\BillingCycle;
use App\Enums\LeaseStatus;
use App\Enums\LeaseType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lease\StoreLeaseRequest;
use App\Http\Requests\Lease\UpdateLeaseRequest;
use App\Models\Building;
use App\Models\Lease;
use App\Models\LeaseHistory;
use App\Models\Property;
use App\Models\RentalTenant;
use App\Models\Unit;
use App\Services\Lease\LeaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaseController extends Controller
{
    public function __construct(
        private readonly LeaseService $leaseService,
    ) {}

    public function index(Request $request): Response
    {
        $leases = Lease::query()
            ->with([
                'rentalTenant:id,first_name,last_name,email,profile_photo',
                'unit:id,unit_number,property_id,building_id',
                'unit.property:id,name',
                'unit.building:id,name',
            ])
            ->withCount(['documents', 'deposits', 'renewals'])
            ->when($request->search, fn ($q, $v) =>
                $q->where(fn ($inner) =>
                    $inner->where('lease_number', 'like', "%{$v}%")
                          ->orWhereHas('rentalTenant', fn ($tq) =>
                              $tq->where('first_name', 'like', "%{$v}%")
                                 ->orWhere('last_name', 'like', "%{$v}%")
                                 ->orWhere('email', 'like', "%{$v}%")
                          )
                )
            )
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->lease_type, fn ($q, $v) => $q->where('lease_type', $v))
            ->when($request->property_id, fn ($q, $v) =>
                $q->whereHas('unit', fn ($uq) => $uq->where('property_id', $v))
            )
            ->when($request->expiring_before, fn ($q, $v) =>
                $q->where('end_date', '<=', $v)
                  ->whereIn('status', [LeaseStatus::Active->value, LeaseStatus::ExpiringSoon->value])
            )
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Lease $lease) => $this->transformLease($lease));

        return Inertia::render('leases/Index', [
            'leases'      => $leases,
            'filters'     => $request->only(['search', 'status', 'lease_type', 'property_id', 'expiring_before']),
            'statuses'    => collect(LeaseStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'leaseTypes'  => collect(LeaseType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'properties'  => Property::orderBy('name')->get(['id', 'name'])->map(fn ($p) => ['value' => $p->id, 'label' => $p->name]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('leases/Create', [
            'statuses'       => collect(LeaseStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'leaseTypes'     => collect(LeaseType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'billingCycles'  => collect(BillingCycle::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'properties'     => Property::with('buildings:id,property_id,name')->orderBy('name')->get(['id', 'name']),
            'tenants'        => RentalTenant::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email'])
                                    ->map(fn ($t) => ['value' => $t->id, 'label' => "{$t->first_name} {$t->last_name}", 'email' => $t->email]),
            'units'          => Unit::orderBy('unit_number')->get(['id', 'property_id', 'building_id', 'unit_number'])
                                    ->map(fn ($u) => [
                                        'id'          => $u->id,
                                        'property_id' => $u->property_id,
                                        'building_id' => $u->building_id,
                                        'unit_number' => $u->unit_number,
                                    ]),
        ]);
    }

    public function store(StoreLeaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['lease_number'] = $this->leaseService->generateLeaseNumber();
        $data['created_by']   = $request->user()->id;

        $lease = Lease::create($data);

        LeaseHistory::record($lease, 'created', 'Lease created.', [], $request->user()->id);

        return redirect()->route('leases.show', $lease)
            ->with('toast', ['type' => 'success', 'message' => "Lease {$lease->lease_number} created successfully."]);
    }

    public function show(Lease $lease): Response
    {
        $lease->load([
            'rentalTenant',
            'unit.property',
            'unit.building',
            'building',
            'deposits' => fn ($q) => $q->orderByDesc('created_at'),
            'renewals' => fn ($q) => $q->orderByDesc('renewal_date'),
            'documents',
            'histories',
            'invoices' => fn ($q) => $q->latest()->limit(10),
        ]);

        $lease->loadCount(['documents', 'deposits', 'renewals', 'invoices']);

        return Inertia::render('leases/Show', [
            'lease'               => $this->transformLeaseDetail($lease),
            'terminationReasons'  => collect(\App\Enums\LeaseTerminationReason::cases())
                                        ->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function edit(Lease $lease): Response
    {
        $lease->load(['unit.property', 'unit.building', 'rentalTenant']);

        return Inertia::render('leases/Edit', [
            'lease'          => $this->transformLeaseDetail($lease),
            'statuses'       => collect(LeaseStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'leaseTypes'     => collect(LeaseType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'billingCycles'  => collect(BillingCycle::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'properties'     => Property::with('buildings:id,property_id,name')->orderBy('name')->get(['id', 'name']),
            'tenants'        => RentalTenant::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email'])
                                    ->map(fn ($t) => ['value' => $t->id, 'label' => "{$t->first_name} {$t->last_name}", 'email' => $t->email]),
            'units'          => Unit::orderBy('unit_number')->get(['id', 'property_id', 'building_id', 'unit_number'])
                                    ->map(fn ($u) => [
                                        'id'          => $u->id,
                                        'property_id' => $u->property_id,
                                        'building_id' => $u->building_id,
                                        'unit_number' => $u->unit_number,
                                    ]),
        ]);
    }

    public function update(UpdateLeaseRequest $request, Lease $lease): RedirectResponse
    {
        $lease->update($request->validated());

        LeaseHistory::record($lease, 'updated', 'Lease details updated.', [], $request->user()->id);

        return redirect()->route('leases.show', $lease)
            ->with('toast', ['type' => 'success', 'message' => 'Lease updated successfully.']);
    }

    public function destroy(Lease $lease): RedirectResponse
    {
        $lease->delete();

        return redirect()->route('leases.index')
            ->with('toast', ['type' => 'success', 'message' => 'Lease deleted.']);
    }

    private function transformLease(Lease $lease): array
    {
        $tenant = $lease->rentalTenant;
        $unit   = $lease->unit;

        return [
            'id'                => $lease->id,
            'lease_number'      => $lease->lease_number,
            'lease_type'        => $lease->lease_type?->value,
            'lease_type_label'  => $lease->lease_type?->label(),
            'status'            => $lease->status?->value,
            'status_label'      => $lease->status?->label(),
            'start_date'        => $lease->start_date?->toDateString(),
            'end_date'          => $lease->end_date?->toDateString(),
            'rent_amount'       => $lease->rent_amount,
            'currency'          => $lease->currency,
            'days_remaining'    => $lease->days_remaining,
            'progress_percentage' => $lease->progress_percentage,
            'tenant' => $tenant ? [
                'id'                => $tenant->id,
                'full_name'         => $tenant->full_name,
                'email'             => $tenant->email,
                'profile_photo_url' => $tenant->profile_photo_url ?? null,
            ] : null,
            'property_name'    => $unit?->property?->name,
            'building_name'    => $unit?->building?->name,
            'unit_number'      => $unit?->unit_number,
            'documents_count'  => $lease->documents_count,
            'deposits_count'   => $lease->deposits_count,
            'renewals_count'   => $lease->renewals_count,
        ];
    }

    private function transformLeaseDetail(Lease $lease): array
    {
        $base = $this->transformLease($lease);

        return array_merge($base, [
            'move_in_date'         => $lease->move_in_date?->toDateString(),
            'move_out_date'        => $lease->move_out_date?->toDateString(),
            'deposit_amount'       => $lease->deposit_amount,
            'advance_payment'      => $lease->advance_payment,
            'utility_deposit'      => $lease->utility_deposit,
            'parking_fee'          => $lease->parking_fee,
            'other_charges'        => $lease->other_charges,
            'billing_day'          => $lease->billing_day,
            'billing_cycle'        => $lease->billing_cycle?->value,
            'generate_days_before' => $lease->generate_days_before,
            'issue_date_offset'    => $lease->issue_date_offset,
            'termination_date'     => $lease->termination_date?->toDateString(),
            'termination_reason'   => $lease->termination_reason,
            'notes'                => $lease->notes,
            'months_remaining'     => $lease->months_remaining,
            'total_monthly_charges' => $lease->total_monthly_charges,
            'unit_id'              => $lease->unit_id,
            'building_id'          => $lease->building_id,
            'rental_tenant_id'     => $lease->rental_tenant_id,
            'unit' => $lease->unit ? [
                'id'          => $lease->unit->id,
                'unit_number' => $lease->unit->unit_number,
                'property_id' => $lease->unit->property_id,
                'property'    => $lease->unit->property ? ['id' => $lease->unit->property->id, 'name' => $lease->unit->property->name] : null,
                'building'    => $lease->unit->building ? ['id' => $lease->unit->building->id, 'name' => $lease->unit->building->name] : null,
            ] : null,
            'building' => $lease->building ? ['id' => $lease->building->id, 'name' => $lease->building->name] : null,
            'tenant' => $lease->rentalTenant ? [
                'id'                => $lease->rentalTenant->id,
                'full_name'         => $lease->rentalTenant->full_name,
                'first_name'        => $lease->rentalTenant->first_name,
                'last_name'         => $lease->rentalTenant->last_name,
                'email'             => $lease->rentalTenant->email,
                'phone'             => $lease->rentalTenant->phone,
                'profile_photo_url' => $lease->rentalTenant->profile_photo_url ?? null,
            ] : null,
            'deposits'  => $lease->deposits?->map(fn ($d) => [
                'id'               => $d->id,
                'type'             => $d->type?->value,
                'type_label'       => $d->type?->label(),
                'amount'           => $d->amount,
                'payment_date'     => $d->payment_date?->toDateString(),
                'status'           => $d->status?->value,
                'status_label'     => $d->status?->label(),
                'refund_amount'    => $d->refund_amount,
                'deduction_amount' => $d->deduction_amount,
                'refund_date'      => $d->refund_date?->toDateString(),
                'notes'            => $d->notes,
            ]),
            'renewals' => $lease->renewals?->map(fn ($r) => [
                'id'                  => $r->id,
                'previous_start_date' => $r->previous_start_date?->toDateString(),
                'previous_end_date'   => $r->previous_end_date?->toDateString(),
                'new_end_date'        => $r->new_end_date?->toDateString(),
                'new_rent_amount'     => $r->new_rent_amount,
                'renewal_date'        => $r->renewal_date?->toDateString(),
                'renewal_status'      => $r->renewal_status?->value,
                'renewal_status_label' => $r->renewal_status?->label(),
                'reason'              => $r->reason,
                'notes'               => $r->notes,
            ]),
            'documents' => $lease->documents?->map(fn ($doc) => [
                'id'          => $doc->id,
                'name'        => $doc->name,
                'type'        => $doc->type,
                'file_path'   => $doc->file_path,
                'file_url'    => \Illuminate\Support\Facades\Storage::url($doc->file_path),
                'file_size'   => $doc->file_size,
                'mime_type'   => $doc->mime_type,
                'uploaded_at' => $doc->created_at?->toISOString(),
            ]),
            'histories' => $lease->histories?->map(fn ($h) => [
                'id'          => $h->id,
                'event_type'  => $h->event_type,
                'description' => $h->description,
                'metadata'    => $h->metadata,
                'occurred_at' => $h->occurred_at?->toISOString(),
            ]),
            'invoices' => $lease->invoices?->map(fn ($inv) => [
                'id'             => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'status'         => $inv->status?->value,
                'status_label'   => $inv->status?->label(),
                'total_amount'   => $inv->total_amount,
                'due_date'       => $inv->due_date?->toDateString(),
            ]),
            'invoices_count' => $lease->invoices_count ?? 0,
        ]);
    }
}
