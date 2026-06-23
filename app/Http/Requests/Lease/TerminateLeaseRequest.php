<?php

declare(strict_types=1);

namespace App\Http\Requests\Lease;

use App\Enums\LeaseTerminationReason;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TerminateLeaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'termination_date'   => ['required', 'date'],
            'termination_reason' => ['required', Rule::enum(LeaseTerminationReason::class)],
            'move_out_date'      => ['nullable', 'date'],
            'notes'              => ['nullable', 'string', 'max:2000'],
        ];
    }
}
