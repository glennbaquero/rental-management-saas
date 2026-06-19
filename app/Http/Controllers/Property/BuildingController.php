<?php

declare(strict_types=1);

namespace App\Http\Controllers\Property;

use App\Enums\BuildingStatus;
use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BuildingController extends Controller
{
    public function store(Request $request, Property $property): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['nullable', 'string', 'max:50'],
            'floors'      => ['required', 'integer', 'min:1', 'max:200'],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', Rule::enum(BuildingStatus::class)],
        ]);

        $property->buildings()->create($data);

        return back()->with('toast', ['type' => 'success', 'message' => 'Building added.']);
    }

    public function update(Request $request, Property $property, Building $building): RedirectResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['nullable', 'string', 'max:50'],
            'floors'      => ['required', 'integer', 'min:1', 'max:200'],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', Rule::enum(BuildingStatus::class)],
        ]);

        $building->update($data);

        return back()->with('toast', ['type' => 'success', 'message' => 'Building updated.']);
    }

    public function destroy(Property $property, Building $building): RedirectResponse
    {
        $building->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Building deleted.']);
    }
}
