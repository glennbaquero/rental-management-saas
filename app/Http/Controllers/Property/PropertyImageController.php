<?php

declare(strict_types=1);

namespace App\Http\Controllers\Property;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PropertyImageController extends Controller
{
    public function store(Request $request, Property $property): RedirectResponse
    {
        $request->validate([
            'images'         => ['required', 'array', 'min:1'],
            'images.*'       => ['image', 'max:8192'],
            'images.*.caption' => ['nullable', 'string', 'max:255'],
        ]);

        $lastOrder = $property->images()->max('sort_order') ?? -1;

        foreach ($request->file('images', []) as $index => $file) {
            $path = $file->store("properties/{$property->id}/gallery", 'public');

            $property->images()->create([
                'path'       => $path,
                'disk'       => 'public',
                'caption'    => $request->input("captions.{$index}"),
                'sort_order' => $lastOrder + $index + 1,
            ]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Images uploaded.']);
    }

    public function destroy(Property $property, PropertyImage $image): RedirectResponse
    {
        Storage::disk($image->disk)->delete($image->path);
        $image->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Image deleted.']);
    }

    public function reorder(Request $request, Property $property): RedirectResponse
    {
        $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['uuid', 'exists:property_images,id'],
        ]);

        foreach ($request->order as $index => $id) {
            $property->images()->where('id', $id)->update(['sort_order' => $index]);
        }

        return back();
    }
}
