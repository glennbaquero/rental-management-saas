<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use App\Enums\MaintenanceAssigneeType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignMaintenanceTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'              => ['nullable', 'uuid', Rule::exists('users', 'id')],
            'assignee_type'        => ['required', Rule::enum(MaintenanceAssigneeType::class)],
            'contractor_name'      => [
                Rule::requiredIf($this->assignee_type === MaintenanceAssigneeType::ExternalContractor->value),
                'nullable', 'string', 'max:255',
            ],
            'contractor_contact'   => ['nullable', 'string', 'max:255'],
            'assigned_date'        => ['required', 'date'],
            'estimated_completion' => ['nullable', 'date', 'after_or_equal:assigned_date'],
            'remarks'              => ['nullable', 'string'],
            'is_primary'           => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_primary' => $this->boolean('is_primary', false),
        ]);
    }
}
