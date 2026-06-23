<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files'   => ['required', 'array', 'max:10'],
            'files.*' => ['file', 'max:51200', 'mimes:jpg,jpeg,png,webp,pdf,mp4,mov'],
        ];
    }
}
