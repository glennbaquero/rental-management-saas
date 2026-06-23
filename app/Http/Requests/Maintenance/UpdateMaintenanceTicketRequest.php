<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use App\Enums\MaintenanceCategory;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaintenanceTicketRequest extends FormRequest
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
            'status'             => ['nullable', Rule::enum(MaintenanceStatus::class)],
            'preferred_schedule' => ['nullable', 'date'],
            'notes'              => ['nullable', 'string'],
        ];
    }
}
