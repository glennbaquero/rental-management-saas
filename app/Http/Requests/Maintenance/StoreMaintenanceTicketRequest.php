<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use App\Enums\MaintenanceCategory;
use App\Enums\MaintenancePriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'property_id'        => ['required', 'uuid', Rule::exists('properties', 'id')],
            'building_id'        => ['nullable', 'uuid', Rule::exists('buildings', 'id')],
            'unit_id'            => ['required', 'uuid', Rule::exists('units', 'id')],
            'rental_tenant_id'   => ['nullable', 'uuid', Rule::exists('rental_tenants', 'id')],
            'category'           => ['required', Rule::enum(MaintenanceCategory::class)],
            'title'              => ['required', 'string', 'max:255'],
            'description'        => ['required', 'string'],
            'priority'           => ['required', Rule::enum(MaintenancePriority::class)],
            'preferred_schedule' => ['nullable', 'date'],
            'notes'              => ['nullable', 'string'],
            'attachments'        => ['nullable', 'array', 'max:10'],
            'attachments.*'      => ['file', 'max:51200', 'mimes:jpg,jpeg,png,webp,pdf,mp4,mov'],
        ];
    }
}
