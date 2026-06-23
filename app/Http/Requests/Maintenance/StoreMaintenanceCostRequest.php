<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use App\Enums\MaintenanceCostStatus;
use App\Enums\MaintenanceCostType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMaintenanceCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cost_type'   => ['required', Rule::enum(MaintenanceCostType::class)],
            'description' => ['required', 'string', 'max:255'],
            'amount'      => ['required', 'numeric', 'min:0'],
            'status'      => ['required', Rule::enum(MaintenanceCostStatus::class)],
            'receipt'     => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf'],
        ];
    }
}
