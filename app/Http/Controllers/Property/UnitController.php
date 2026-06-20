<?php

declare(strict_types=1);

namespace App\Http\Controllers\Property;

use App\Enums\UnitStatus;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function index(Request $request, Property $property): \Inertia\Response
    {
        $units = $property->units()
            ->with(['building:id,name', 'unitType:id,name'])
            ->when($request->building,   fn ($query, $value) => $query->where('building_id', $value))
            ->when($request->unit_type,  fn ($query, $value) => $query->where('unit_type_id', $value))
            ->when($request->status,     fn ($query, $value) => $query->where('status', $value))
            ->when($request->search,     fn ($query, $value) =>
                $query->where('unit_number', 'like', "%{$value}%")
            )
            ->orderByRaw("ISNULL(building_id), building_id")
            ->orderBy('unit_number')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Unit $unit) => $this->transformUnit($unit));

        return \Inertia::render('properties/units/Index', [
            'property' => ['id' => $property->id, 'name' => $property->name],
            'units'    => $units,
            'filters'  => $request->only(['search', 'building', 'unit_type', 'status']),
            'statuses' => collect(UnitStatus::cases())->map(fn ($case) => ['value' => $case->value, 'label' => $case->label()]),
        ]);
    }

    public function store(Request $request, Property $property): RedirectResponse
    {
        $data = $request->validate([
            'unit_number'    => ['required', 'string', 'max:20', Rule::unique('units')->where('property_id', $property->id)],
            'building_id'    => ['nullable', 'uuid', Rule::exists('buildings', 'id')->where('property_id', $property->id)],
            'unit_type_id'   => ['nullable', 'uuid', 'exists:unit_types,id'],
            'floor'          => ['nullable', 'string', 'max:20'],
            'area_sqm'       => ['nullable', 'numeric', 'min:0'],
            'bedrooms'       => ['nullable', 'integer', 'min:0'],
            'bathrooms'      => ['nullable', 'integer', 'min:0'],
            'max_occupants'  => ['nullable', 'integer', 'min:1'],
            'rent_amount'    => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'status'         => ['nullable', Rule::enum(UnitStatus::class)],
            'notes'          => ['nullable', 'string'],
        ]);

        $property->units()->create($data);

        return back()->with('toast', ['type' => 'success', 'message' => 'Unit added.']);
    }

    public function update(Request $request, Property $property, Unit $unit): RedirectResponse
    {
        $data = $request->validate([
            'unit_number'    => ['required', 'string', 'max:20', Rule::unique('units')->where('property_id', $property->id)->ignore($unit->id)],
            'building_id'    => ['nullable', 'uuid', Rule::exists('buildings', 'id')->where('property_id', $property->id)],
            'unit_type_id'   => ['nullable', 'uuid', 'exists:unit_types,id'],
            'floor'          => ['nullable', 'string', 'max:20'],
            'area_sqm'       => ['nullable', 'numeric', 'min:0'],
            'bedrooms'       => ['nullable', 'integer', 'min:0'],
            'bathrooms'      => ['nullable', 'integer', 'min:0'],
            'max_occupants'  => ['nullable', 'integer', 'min:1'],
            'rent_amount'    => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'status'         => ['nullable', Rule::enum(UnitStatus::class)],
            'notes'          => ['nullable', 'string'],
        ]);

        $unit->update($data);

        return back()->with('toast', ['type' => 'success', 'message' => 'Unit updated.']);
    }

    public function destroy(Property $property, Unit $unit): RedirectResponse
    {
        $unit->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Unit deleted.']);
    }

    private function transformUnit(Unit $unit): array
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
}
