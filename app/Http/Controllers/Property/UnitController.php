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
            ->when($request->building, fn ($q, $v) => $q->where('building_id', $v))
            ->when($request->unit_type, fn ($q, $v) => $q->where('unit_type_id', $v))
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->search, fn ($q, $v) =>
                $q->where('unit_number', 'like', "%{$v}%")
            )
            ->orderByRaw("ISNULL(building_id), building_id")
            ->orderBy('unit_number')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Unit $u) => $this->transformUnit($u));

        return \Inertia::render('properties/units/Index', [
            'property' => ['id' => $property->id, 'name' => $property->name],
            'units'    => $units,
            'filters'  => $request->only(['search', 'building', 'unit_type', 'status']),
            'statuses' => collect(UnitStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
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

    private function transformUnit(Unit $u): array
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
}
