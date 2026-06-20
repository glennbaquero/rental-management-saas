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
                'units as available_units_count' => fn ($query) => $query->where('status', 'available'),
                'units as occupied_units_count'  => fn ($query) => $query->where('status', 'occupied'),
            ])
            ->when($request->search, fn ($query, $value) =>
                $query->where(fn ($inner) =>
                    $inner->where('name', 'like', "%{$value}%")
                          ->orWhere('code', 'like', "%{$value}%")
                          ->orWhere('address', 'like', "%{$value}%")
                )
            )
            ->when($request->type,   fn ($query, $value) => $query->ofType($value))
            ->when($request->status, fn ($query, $value) => $query->ofStatus($value))
            ->when($request->city,   fn ($query, $value) => $query->inCity($value))
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Property $property) => $this->transformProperty($property));

        $cities = Property::select('city')->distinct()->orderBy('city')->pluck('city');

        return Inertia::render('properties/Index', [
            'properties' => $properties,
            'filters'    => $request->only(['search', 'type', 'status', 'city']),
            'cities'     => $cities,
            'types'      => collect(PropertyType::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
            'statuses'   => collect(PropertyStatus::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('properties/Create', [
            'types'    => collect(PropertyType::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
            'statuses' => collect(PropertyStatus::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
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
            'units as available_units_count'   => fn ($query) => $query->where('status', 'available'),
            'units as occupied_units_count'    => fn ($query) => $query->where('status', 'occupied'),
            'units as reserved_units_count'    => fn ($query) => $query->where('status', 'reserved'),
            'units as maintenance_units_count' => fn ($query) => $query->where('status', 'maintenance'),
        ])->load(['amenities', 'images']);

        $monthlyRevenue = $property->units()->where('status', 'occupied')->sum('rent_amount');

        $buildings = $property->buildings()
            ->withCount([
                'units',
                'units as occupied_units_count'  => fn ($query) => $query->where('status', 'occupied'),
                'units as available_units_count' => fn ($query) => $query->where('status', 'available'),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn ($building) => $this->transformBuilding($building));

        $units = $property->units()
            ->with(['building:id,name', 'unitType:id,name'])
            ->orderByRaw('ISNULL(building_id), building_id')
            ->orderBy('unit_number')
            ->get()
            ->map(fn ($unit) => $this->transformUnit($unit));

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
            'types'    => collect(PropertyType::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
            'statuses' => collect(PropertyStatus::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
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

    private function transformProperty(Property $property, float $monthlyRevenue = 0): array
    {
        return [
            'id'                     => $property->id,
            'name'                   => $property->name,
            'code'                   => $property->code,
            'type'                   => $property->type?->value,
            'type_label'             => $property->type?->label(),
            'description'            => $property->description,
            'address'                => $property->address,
            'city'                   => $property->city,
            'province'               => $property->province,
            'state'                  => $property->state,
            'zip'                    => $property->zip,
            'postal_code'            => $property->postal_code,
            'country'                => $property->country,
            'latitude'               => $property->latitude,
            'longitude'              => $property->longitude,
            'status'                 => $property->status?->value,
            'status_label'           => $property->status?->label(),
            'featured_image_url'     => $property->featured_image_url,
            'total_buildings'        => $property->buildings_count ?? 0,
            'total_units'            => $property->units_count ?? 0,
            'available_units'        => $property->available_units_count ?? 0,
            'occupied_units'         => $property->occupied_units_count ?? 0,
            'reserved_units'         => $property->reserved_units_count ?? 0,
            'maintenance_units'      => $property->maintenance_units_count ?? 0,
            'monthly_revenue'        => (float) $monthlyRevenue,
            'amenities'              => $property->relationLoaded('amenities')
                ? $property->amenities->map(fn ($amenity) => ['id' => $amenity->id, 'name' => $amenity->name, 'icon' => $amenity->icon, 'category' => $amenity->category])->values()
                : [],
            'images'                 => $property->relationLoaded('images')
                ? $property->images->map(fn ($image) => ['id' => $image->id, 'url' => $image->url, 'caption' => $image->caption, 'sort_order' => $image->sort_order])->values()
                : [],
            'created_at'             => $property->created_at?->toISOString(),
        ];
    }

    private function transformUnit(\App\Models\Unit $unit): array
    {
        return [
            'id'             => $unit->id,
            'unit_number'    => $unit->unit_number,
            'floor'          => $unit->floor,
            'area_sqm'       => $unit->area_sqm,
            'bedrooms'       => $unit->bedrooms,
            'bathrooms'      => $unit->bathrooms,
            'max_occupants'  => $unit->max_occupants,
            'rent_amount'    => (float) $unit->rent_amount,
            'deposit_amount' => (float) $unit->deposit_amount,
            'status'         => $unit->status?->value,
            'status_label'   => $unit->status?->label(),
            'notes'          => $unit->notes,
            'building'       => $unit->building ? ['id' => $unit->building->id, 'name' => $unit->building->name] : null,
            'unit_type'      => $unit->unitType ? ['id' => $unit->unitType->id, 'name' => $unit->unitType->name] : null,
        ];
    }

    private function transformBuilding(\App\Models\Building $building): array
    {
        return [
            'id'              => $building->id,
            'name'            => $building->name,
            'code'            => $building->code,
            'floors'          => $building->floors,
            'description'     => $building->description,
            'status'          => $building->status?->value,
            'status_label'    => $building->status?->label(),
            'total_units'     => $building->units_count ?? 0,
            'occupied_units'  => $building->occupied_units_count ?? 0,
            'available_units' => $building->available_units_count ?? 0,
        ];
    }
}
