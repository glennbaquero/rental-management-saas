<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\IdDocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantIdDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'            => ['required', Rule::enum(IdDocumentType::class)],
            'document_number' => ['nullable', 'string', 'max:100'],
            'issued_by'       => ['nullable', 'string', 'max:255'],
            'issued_date'     => ['nullable', 'date'],
            'expiry_date'     => ['nullable', 'date', 'after:issued_date'],
            'front_image'     => ['nullable', 'image', 'max:5120'],
            'back_image'      => ['nullable', 'image', 'max:5120'],
        ];
    }
}
