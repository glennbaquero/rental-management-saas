<?php

declare(strict_types=1);

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\Property;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AmenityController extends Controller
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $amenities = Amenity::orderBy('category')->orderBy('name')->get();

        return response()->json($amenities);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'icon'     => ['nullable', 'string', 'max:100'],
            'category' => ['required', 'in:property,unit,both'],
        ]);

        $data['slug']      = \Illuminate\Support\Str::slug($data['name']);
        $data['is_system'] = false;

        Amenity::create($data);

        return back()->with('toast', ['type' => 'success', 'message' => 'Amenity created.']);
    }

    public function syncProperty(Request $request, Property $property): RedirectResponse
    {
        $request->validate([
            'amenity_ids'   => ['nullable', 'array'],
            'amenity_ids.*' => ['uuid', 'exists:amenities,id'],
        ]);

        $property->amenities()->sync($request->amenity_ids ?? []);

        return back()->with('toast', ['type' => 'success', 'message' => 'Amenities updated.']);
    }

    public function syncUnit(Request $request, Property $property, Unit $unit): RedirectResponse
    {
        $request->validate([
            'amenity_ids'   => ['nullable', 'array'],
            'amenity_ids.*' => ['uuid', 'exists:amenities,id'],
        ]);

        $unit->amenities()->sync($request->amenity_ids ?? []);

        return back()->with('toast', ['type' => 'success', 'message' => 'Unit amenities updated.']);
    }

    public function destroy(Amenity $amenity): RedirectResponse
    {
        $amenity->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Amenity deleted.']);
    }
}
