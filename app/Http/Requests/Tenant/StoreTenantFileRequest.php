<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use App\Enums\TenantFileType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx'],
            'type' => ['required', Rule::enum(TenantFileType::class)],
            'name' => ['nullable', 'string', 'max:255'],
        ];
    }
}
