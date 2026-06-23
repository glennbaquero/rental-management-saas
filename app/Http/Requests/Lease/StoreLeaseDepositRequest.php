<?php

declare(strict_types=1);

namespace App\Http\Requests\Lease;

use App\Enums\LeaseDepositStatus;
use App\Enums\LeaseDepositType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeaseDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount'           => $this->amount !== '' ? $this->amount : null,
            'refund_amount'    => $this->refund_amount !== '' ? $this->refund_amount : null,
            'deduction_amount' => $this->deduction_amount !== '' ? $this->deduction_amount : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'type'             => ['required', Rule::enum(LeaseDepositType::class)],
            'amount'           => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'payment_date'     => ['nullable', 'date'],
            'status'           => ['required', Rule::enum(LeaseDepositStatus::class)],
            'refund_amount'    => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'deduction_amount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'refund_date'      => ['nullable', 'date'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ];
    }
}
