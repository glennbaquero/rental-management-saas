<?php

declare(strict_types=1);

namespace App\Http\Requests\Property;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $propertyId = $this->route('property')?->id;

        return [
            'name'            => ['required', 'string', 'max:255'],
            'code'            => ['nullable', 'string', 'max:50', Rule::unique('properties', 'code')->ignore($propertyId)],
            'type'            => ['required', Rule::enum(PropertyType::class)],
            'description'     => ['nullable', 'string'],
            'address'         => ['required', 'string', 'max:255'],
            'city'            => ['required', 'string', 'max:100'],
            'province'        => ['nullable', 'string', 'max:100'],
            'state'           => ['nullable', 'string', 'max:100'],
            'zip'             => ['nullable', 'string', 'max:20'],
            'postal_code'     => ['nullable', 'string', 'max:20'],
            'country'         => ['nullable', 'string', 'max:2'],
            'latitude'        => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'       => ['nullable', 'numeric', 'between:-180,180'],
            'status'          => ['nullable', Rule::enum(PropertyStatus::class)],
            'featured_image'  => ['nullable', 'image', 'max:4096'],
        ];
    }
}
