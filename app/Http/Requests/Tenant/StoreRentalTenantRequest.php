<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\CivilStatus;
use App\Enums\Gender;
use App\Enums\RentalTenantStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRentalTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'gender'         => $this->gender ?: null,
            'civil_status'   => $this->civil_status ?: null,
            'status'         => $this->status ?: null,
            'monthly_income' => $this->monthly_income !== '' ? $this->monthly_income : null,
            'profile_photo'  => $this->hasFile('profile_photo') ? $this->file('profile_photo') : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name'      => ['required', 'string', 'max:255'],
            'middle_name'     => ['nullable', 'string', 'max:255'],
            'last_name'       => ['required', 'string', 'max:255'],
            'email'           => ['nullable', 'email', 'max:255'],
            'phone'           => ['nullable', 'string', 'max:30'],
            'alternate_phone' => ['nullable', 'string', 'max:30'],
            'date_of_birth'   => ['nullable', 'date', 'before:today'],
            'gender'          => ['nullable', Rule::enum(Gender::class)],
            'civil_status'    => ['nullable', Rule::enum(CivilStatus::class)],
            'nationality'     => ['nullable', 'string', 'max:100'],
            'current_address' => ['nullable', 'string', 'max:500'],
            'city'            => ['nullable', 'string', 'max:100'],
            'province'        => ['nullable', 'string', 'max:100'],
            'country'         => ['nullable', 'string', 'max:100'],
            'postal_code'     => ['nullable', 'string', 'max:20'],
            'occupation'      => ['nullable', 'string', 'max:255'],
            'employer'        => ['nullable', 'string', 'max:255'],
            'employer_address' => ['nullable', 'string', 'max:500'],
            'monthly_income'  => ['nullable', 'numeric', 'min:0'],
            'status'          => ['nullable', Rule::enum(RentalTenantStatus::class)],
            'notes'           => ['nullable', 'string'],
            'profile_photo'   => ['nullable', 'image', 'max:2048'],
        ];
    }
}
