<?php

declare(strict_types=1);

namespace App\Http\Controllers\Property;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use App\Models\Amenity;
use App\Models\Property;
use App\Models\UnitType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PropertyController extends Controller
{
    public function index(Request $request): Response
    {
        $properties = Property::query()
            ->withCount([
                'buildings',
                'units',
                'units as available_units_count' => fn ($q) => $q->where('status', 'available'),
                'units as occupied_units_count'  => fn ($q) => $q->where('status', 'occupied'),
            ])
            ->when($request->search, fn ($q, $v) =>
                $q->where(fn ($inner) =>
                    $inner->where('name', 'like', "%{$v}%")
                          ->orWhere('code', 'like', "%{$v}%")
                          ->orWhere('address', 'like', "%{$v}%")
                )
            )
            ->when($request->type,   fn ($q, $v) => $q->ofType($v))
            ->when($request->status, fn ($q, $v) => $q->ofStatus($v))
            ->when($request->city,   fn ($q, $v) => $q->inCity($v))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Property $p) => $this->transformProperty($p));

        $cities = Property::select('city')->distinct()->orderBy('city')->pluck('city');

        return Inertia::render('properties/Index', [
            'properties' => $properties,
            'filters'    => $request->only(['search', 'type', 'status', 'city']),
            'cities'     => $cities,
            'types'      => collect(PropertyType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'statuses'   => collect(PropertyStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('properties/Create', [
            'types'    => collect(PropertyType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'statuses' => collect(PropertyStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')
                ->store('properties/featured', 'public');
        }

        $property = Property::create($data);

        return redirect()->route('properties.show', $property)
            ->with('toast', ['type' => 'success', 'message' => 'Property created successfully.']);
    }

    public function show(Property $property): Response
    {
        $property->loadCount([
            'buildings',
            'units',
            'units as available_units_count' => fn ($q) => $q->where('status', 'available'),
            'units as occupied_units_count'  => fn ($q) => $q->where('status', 'occupied'),
            'units as reserved_units_count'  => fn ($q) => $q->where('status', 'reserved'),
            'units as maintenance_units_count' => fn ($q) => $q->where('status', 'maintenance'),
        ])->load(['amenities', 'images']);

        $monthlyRevenue = $property->units()->where('status', 'occupied')->sum('rent_amount');

        $buildings = $property->buildings()
            ->withCount([
                'units',
                'units as occupied_units_count'  => fn ($q) => $q->where('status', 'occupied'),
                'units as available_units_count' => fn ($q) => $q->where('status', 'available'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn ($b) => $this->transformBuilding($b));

        $units = $property->units()
            ->with(['building:id,name', 'unitType:id,name'])
            ->orderByRaw('ISNULL(building_id), building_id')
            ->orderBy('unit_number')
            ->get()
            ->map(fn ($u) => $this->transformUnit($u));

        $unitTypes    = UnitType::orderBy('name')->get(['id', 'name']);
        $allAmenities = Amenity::orderBy('name')->get(['id', 'name', 'icon', 'category']);

        return Inertia::render('properties/Show', [
            'property'     => $this->transformProperty($property, $monthlyRevenue),
            'buildings'    => $buildings,
            'units'        => $units,
            'unitTypes'    => $unitTypes,
            'allAmenities' => $allAmenities,
        ]);
    }

    public function edit(Property $property): Response
    {
        $property->load('images');

        return Inertia::render('properties/Edit', [
            'property' => $this->transformProperty($property),
            'types'    => collect(PropertyType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'statuses' => collect(PropertyStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function update(UpdatePropertyRequest $request, Property $property): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('featured_image')) {
            if ($property->featured_image) {
                Storage::disk('public')->delete($property->featured_image);
            }
            $data['featured_image'] = $request->file('featured_image')
                ->store('properties/featured', 'public');
        }

        $property->update($data);

        return redirect()->route('properties.show', $property)
            ->with('toast', ['type' => 'success', 'message' => 'Property updated successfully.']);
    }

    public function destroy(Property $property): RedirectResponse
    {
        $property->delete();

        return redirect()->route('properties.index')
            ->with('toast', ['type' => 'success', 'message' => 'Property deleted.']);
    }

    private function transformProperty(Property $p, float $monthlyRevenue = 0): array
    {
        return [
            'id'                     => $p->id,
            'name'                   => $p->name,
            'code'                   => $p->code,
            'type'                   => $p->type?->value,
            'type_label'             => $p->type?->label(),
            'description'            => $p->description,
            'address'                => $p->address,
            'city'                   => $p->city,
            'province'               => $p->province,
            'state'                  => $p->state,
            'zip'                    => $p->zip,
            'postal_code'            => $p->postal_code,
            'country'                => $p->country,
            'latitude'               => $p->latitude,
            'longitude'              => $p->longitude,
            'status'                 => $p->status?->value,
            'status_label'           => $p->status?->label(),
            'featured_image_url'     => $p->featured_image_url,
            'total_buildings'        => $p->buildings_count ?? 0,
            'total_units'            => $p->units_count ?? 0,
            'available_units'        => $p->available_units_count ?? 0,
            'occupied_units'         => $p->occupied_units_count ?? 0,
            'reserved_units'         => $p->reserved_units_count ?? 0,
            'maintenance_units'      => $p->maintenance_units_count ?? 0,
            'monthly_revenue'        => (float) $monthlyRevenue,
            'amenities'              => $p->relationLoaded('amenities')
                ? $p->amenities->map(fn ($a) => ['id' => $a->id, 'name' => $a->name, 'icon' => $a->icon, 'category' => $a->category])->values()
                : [],
            'images'                 => $p->relationLoaded('images')
                ? $p->images->map(fn ($img) => ['id' => $img->id, 'url' => $img->url, 'caption' => $img->caption, 'sort_order' => $img->sort_order])->values()
                : [],
            'created_at'             => $p->created_at?->toISOString(),
        ];
    }

    private function transformUnit(\App\Models\Unit $u): array
    {
        return [
            'id'             => $u->id,
            'unit_number'    => $u->unit_number,
            'floor'          => $u->floor,
            'area_sqm'       => $u->area_sqm,
            'bedrooms'       => $u->bedrooms,
            'bathrooms'      => $u->bathrooms,
            'max_occupants'  => $u->max_occupants,
            'rent_amount'    => (float) $u->rent_amount,
            'deposit_amount' => (float) $u->deposit_amount,
            'status'         => $u->status?->value,
            'status_label'   => $u->status?->label(),
            'notes'          => $u->notes,
            'building'       => $u->building ? ['id' => $u->building->id, 'name' => $u->building->name] : null,
            'unit_type'      => $u->unitType ? ['id' => $u->unitType->id, 'name' => $u->unitType->name] : null,
        ];
    }

    private function transformBuilding(\App\Models\Building $b): array
    {
        return [
            'id'             => $b->id,
            'name'           => $b->name,
            'code'           => $b->code,
            'floors'         => $b->floors,
            'description'    => $b->description,
            'status'         => $b->status?->value,
            'status_label'   => $b->status?->label(),
            'total_units'    => $b->units_count ?? 0,
            'occupied_units' => $b->occupied_units_count ?? 0,
            'available_units' => $b->available_units_count ?? 0,
        ];
    }
}
