<?php

declare(strict_types=1);

namespace App\Http\Requests\Lease;

use App\Enums\BillingCycle;
use App\Enums\LeaseStatus;
use App\Enums\LeaseType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'rent_amount'     => filled($this->rent_amount) ? $this->rent_amount : null,
            'deposit_amount'  => filled($this->deposit_amount) ? $this->deposit_amount : 0,
            'advance_payment' => filled($this->advance_payment) ? $this->advance_payment : 0,
            'utility_deposit' => filled($this->utility_deposit) ? $this->utility_deposit : 0,
            'parking_fee'     => filled($this->parking_fee) ? $this->parking_fee : 0,
            'other_charges'   => filled($this->other_charges) ? $this->other_charges : 0,
        ]);
    }

    public function rules(): array
    {
        return [
            'unit_id'              => ['sometimes', 'uuid', Rule::exists('units', 'id')],
            'building_id'          => ['nullable', 'uuid', Rule::exists('buildings', 'id')],
            'rental_tenant_id'     => ['sometimes', 'uuid', Rule::exists('rental_tenants', 'id')],
            'lease_type'           => ['sometimes', Rule::enum(LeaseType::class)],
            'status'               => ['sometimes', Rule::enum(LeaseStatus::class)],
            'start_date'           => ['sometimes', 'date'],
            'end_date'             => ['sometimes', 'date', 'after:start_date'],
            'move_in_date'         => ['nullable', 'date'],
            'move_out_date'        => ['nullable', 'date'],
            'rent_amount'          => ['sometimes', 'numeric', 'min:0'],
            'deposit_amount'       => ['nullable', 'numeric', 'min:0'],
            'advance_payment'      => ['nullable', 'numeric', 'min:0'],
            'utility_deposit'      => ['nullable', 'numeric', 'min:0'],
            'parking_fee'          => ['nullable', 'numeric', 'min:0'],
            'other_charges'        => ['nullable', 'numeric', 'min:0'],
            'currency'             => ['nullable', 'string', 'size:3'],
            'billing_day'          => ['sometimes', 'integer', 'min:1', 'max:28'],
            'billing_cycle'        => ['sometimes', Rule::enum(BillingCycle::class)],
            'generate_days_before' => ['nullable', 'integer', 'min:0', 'max:30'],
            'issue_date_offset'    => ['nullable', 'integer', 'min:0', 'max:28'],
            'notes'                => ['nullable', 'string', 'max:2000'],
        ];
    }
}
