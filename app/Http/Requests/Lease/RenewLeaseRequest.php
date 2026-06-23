<?php

declare(strict_types=1);

namespace App\Http\Requests\Lease;

use Illuminate\Foundation\Http\FormRequest;

class RenewLeaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'new_rent_amount' => $this->new_rent_amount !== '' ? $this->new_rent_amount : null,
        ]);
    }

    public function rules(): array
    {
        $lease = $this->route('lease');

        return [
            'new_end_date'    => ['required', 'date', 'after:' . ($lease?->end_date?->toDateString() ?? 'today')],
            'new_rent_amount' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'reason'          => ['nullable', 'string', 'max:1000'],
            'notes'           => ['nullable', 'string', 'max:2000'],
        ];
    }
}
