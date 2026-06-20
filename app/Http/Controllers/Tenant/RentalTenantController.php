<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Enums\CivilStatus;
use App\Enums\Gender;
use App\Enums\RentalTenantStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreRentalTenantRequest;
use App\Http\Requests\Tenant\UpdateRentalTenantRequest;
use App\Models\Property;
use App\Models\RentalTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RentalTenantController extends Controller
{
    public function index(Request $request): Response
    {
        $tenants = RentalTenant::query()
            ->withCount(['leases', 'idDocuments', 'emergencyContacts'])
            ->with(['leases' => fn ($query) => $query->where('status', 'active')
                ->with(['unit:id,unit_number,building_id', 'unit.building:id,name', 'unit.property:id,name'])
                ->latest('start_date')
                ->limit(1),
            ])
            ->when($request->search, fn ($query, $value) =>
                $query->where(fn ($inner) =>
                    $inner->where('first_name', 'like', "%{$value}%")
                          ->orWhere('last_name', 'like', "%{$value}%")
                          ->orWhere('middle_name', 'like', "%{$value}%")
                          ->orWhere('email', 'like', "%{$value}%")
                          ->orWhere('phone', 'like', "%{$value}%")
                          ->orWhere('tenant_code', 'like', "%{$value}%")
                )
            )
            ->when($request->status, fn ($query, $value) => $query->where('status', $value))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (RentalTenant $tenant) => $this->transformTenant($tenant));

        $properties = Property::orderBy('name')->get(['id', 'name'])
            ->map(fn ($property) => ['value' => $property->id, 'label' => $property->name]);

        return Inertia::render('tenants/Index', [
            'tenants'    => $tenants,
            'filters'    => $request->only(['search', 'status']),
            'statuses'   => collect(RentalTenantStatus::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
            'properties' => $properties,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('tenants/Create', [
            'statuses'      => collect(RentalTenantStatus::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
            'genders'       => collect(Gender::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
            'civilStatuses' => collect(CivilStatus::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
        ]);
    }

    public function store(StoreRentalTenantRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $data['tenant_code'] = $this->generateTenantCode();
        $data['created_by']  = $request->user()->id;

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo'] = $request->file('profile_photo')
                ->store('tenants/photos', 'public');
        }

        $tenant = RentalTenant::create($data);

        return redirect()->route('tenants.show', $tenant)
            ->with('toast', ['type' => 'success', 'message' => 'Tenant created successfully.']);
    }

    public function show(RentalTenant $tenant): Response
    {
        $tenant->loadCount(['leases', 'idDocuments', 'emergencyContacts']);

        $activeLease = $tenant->leases()
            ->where('status', 'active')
            ->with(['unit:id,unit_number,building_id', 'unit.building:id,name', 'unit.property:id,name,id'])
            ->latest('start_date')
            ->first();

        $idDocuments = $tenant->idDocuments()->orderBy('created_at', 'desc')->get()
            ->map(fn ($document) => $this->transformIdDocument($document));

        $emergencyContacts = $tenant->emergencyContacts()
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get()
            ->map(fn ($contact) => $this->transformEmergencyContact($contact));

        $tenantFiles = $tenant->tenantFiles()->orderBy('created_at', 'desc')->get()
            ->map(fn ($file) => $this->transformTenantFile($file));

        $rentalHistory = $tenant->leases()
            ->with(['unit:id,unit_number,building_id', 'unit.building:id,name', 'unit.property:id,name'])
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(fn ($lease) => $this->transformRentalHistory($lease));

        return Inertia::render('tenants/Show', [
            'tenant'            => $this->transformTenant($tenant, $activeLease),
            'idDocuments'       => $idDocuments,
            'emergencyContacts' => $emergencyContacts,
            'tenantFiles'       => $tenantFiles,
            'rentalHistory'     => $rentalHistory,
        ]);
    }

    public function edit(RentalTenant $tenant): Response
    {
        return Inertia::render('tenants/Edit', [
            'tenant'        => $this->transformTenant($tenant),
            'statuses'      => collect(RentalTenantStatus::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
            'genders'       => collect(Gender::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
            'civilStatuses' => collect(CivilStatus::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
        ]);
    }

    public function update(UpdateRentalTenantRequest $request, RentalTenant $tenant): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('profile_photo')) {
            if ($tenant->profile_photo) {
                Storage::disk('public')->delete($tenant->profile_photo);
            }
            $data['profile_photo'] = $request->file('profile_photo')
                ->store('tenants/photos', 'public');
        }

        $tenant->update($data);

        return redirect()->route('tenants.show', $tenant)
            ->with('toast', ['type' => 'success', 'message' => 'Tenant updated successfully.']);
    }

    public function destroy(RentalTenant $tenant): RedirectResponse
    {
        $tenant->delete();

        return redirect()->route('tenants.index')
            ->with('toast', ['type' => 'success', 'message' => 'Tenant archived successfully.']);
    }

    private function generateTenantCode(): string
    {
        $last = RentalTenant::withTrashed()->orderBy('created_at', 'desc')->first();
        $next = $last ? ((int) ltrim(str_replace('TC-', '', $last->tenant_code ?? 'TC-0000'), '0') + 1) : 1;
        return 'TC-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function transformTenant(RentalTenant $tenant, $activeLease = null): array
    {
        $currentLease = null;
        if ($activeLease) {
            $unit     = $activeLease->unit;
            $building = $unit?->building;
            $property = $unit?->property;

            $currentLease = [
                'id'            => $activeLease->id,
                'unit_number'   => $unit?->unit_number,
                'building_name' => $building?->name,
                'property_name' => $property?->name,
                'property_id'   => $property?->id,
                'rent_amount'   => (float) $activeLease->rent_amount,
                'start_date'    => $activeLease->start_date?->toDateString(),
                'end_date'      => $activeLease->end_date?->toDateString(),
            ];
        } elseif ($tenant->relationLoaded('leases') && $tenant->leases->isNotEmpty()) {
            $lease    = $tenant->leases->first();
            $unit     = $lease?->unit;
            $building = $unit?->building;
            $property = $unit?->property;

            if ($lease) {
                $currentLease = [
                    'id'            => $lease->id,
                    'unit_number'   => $unit?->unit_number,
                    'building_name' => $building?->name,
                    'property_name' => $property?->name,
                    'property_id'   => $property?->id,
                    'rent_amount'   => (float) $lease->rent_amount,
                    'start_date'    => $lease->start_date?->toDateString(),
                    'end_date'      => $lease->end_date?->toDateString(),
                ];
            }
        }

        return [
            'id'                       => $tenant->id,
            'tenant_code'              => $tenant->tenant_code,
            'first_name'               => $tenant->first_name,
            'middle_name'              => $tenant->middle_name,
            'last_name'                => $tenant->last_name,
            'full_name'                => $tenant->full_name,
            'profile_photo_url'        => $tenant->profile_photo_url,
            'date_of_birth'            => $tenant->date_of_birth?->toDateString(),
            'gender'                   => $tenant->gender?->value,
            'gender_label'             => $tenant->gender?->label(),
            'civil_status'             => $tenant->civil_status?->value,
            'civil_status_label'       => $tenant->civil_status?->label(),
            'nationality'              => $tenant->nationality,
            'email'                    => $tenant->email,
            'phone'                    => $tenant->phone,
            'alternate_phone'          => $tenant->alternate_phone,
            'current_address'          => $tenant->current_address,
            'city'                     => $tenant->city,
            'province'                 => $tenant->province,
            'country'                  => $tenant->country,
            'postal_code'              => $tenant->postal_code,
            'occupation'               => $tenant->occupation,
            'employer'                 => $tenant->employer,
            'employer_address'         => $tenant->employer_address,
            'monthly_income'           => $tenant->monthly_income !== null ? (float) $tenant->monthly_income : null,
            'status'                   => $tenant->status?->value,
            'status_label'             => $tenant->status?->label(),
            'notes'                    => $tenant->notes,
            'leases_count'             => $tenant->leases_count ?? 0,
            'id_documents_count'       => $tenant->id_documents_count ?? 0,
            'emergency_contacts_count' => $tenant->emergency_contacts_count ?? 0,
            'current_lease'            => $currentLease,
            'created_at'               => $tenant->created_at?->toISOString(),
        ];
    }

    private function transformIdDocument(\App\Models\TenantIdDocument $document): array
    {
        return [
            'id'                        => $document->id,
            'type'                      => $document->type?->value,
            'type_label'                => $document->type?->label(),
            'document_number'           => $document->document_number,
            'issued_by'                 => $document->issued_by,
            'issued_date'               => $document->issued_date?->toDateString(),
            'expiry_date'               => $document->expiry_date?->toDateString(),
            'front_image_url'           => $document->front_image_url,
            'back_image_url'            => $document->back_image_url,
            'verification_status'       => $document->verification_status?->value,
            'verification_status_label' => $document->verification_status?->label(),
            'is_expired'                => $document->isExpired(),
        ];
    }

    private function transformEmergencyContact(\App\Models\EmergencyContact $contact): array
    {
        return [
            'id'               => $contact->id,
            'name'             => $contact->name,
            'relationship'     => $contact->relationship,
            'phone'            => $contact->phone,
            'alternate_number' => $contact->alternate_number,
            'email'            => $contact->email,
            'address'          => $contact->address,
            'is_primary'       => $contact->is_primary,
        ];
    }

    private function transformTenantFile(\App\Models\TenantFile $file): array
    {
        return [
            'id'         => $file->id,
            'name'       => $file->name,
            'type'       => $file->type?->value,
            'type_label' => $file->type?->label(),
            'url'        => $file->url,
            'mime_type'  => $file->mime_type,
            'file_size'  => $file->file_size,
            'created_at' => $file->created_at?->toISOString(),
        ];
    }

    private function transformRentalHistory(\App\Models\Lease $lease): array
    {
        $unit     = $lease->unit;
        $building = $unit?->building;
        $property = $unit?->property;

        return [
            'id'             => $lease->id,
            'property_name'  => $property?->name ?? '—',
            'building_name'  => $building?->name,
            'unit_number'    => $unit?->unit_number ?? '—',
            'start_date'     => $lease->start_date?->toDateString(),
            'end_date'       => $lease->end_date?->toDateString(),
            'monthly_rent'   => (float) $lease->rent_amount,
            'deposit_amount' => (float) $lease->deposit_amount,
            'status'         => $lease->status?->value,
            'status_label'   => $lease->status?->label(),
            'move_in_date'   => $lease->start_date?->toDateString(),
            'move_out_date'  => $lease->termination_date?->toDateString() ?? ($lease->status?->value === 'expired' ? $lease->end_date?->toDateString() : null),
            'remarks'        => $lease->notes,
        ];
    }
}
