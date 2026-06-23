<?php

declare(strict_types=1);

namespace App\Http\Requests\Maintenance;

use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating'           => ['required', 'integer', 'min:1', 'max:5'],
            'feedback'         => ['nullable', 'string'],
            'would_recommend'  => ['nullable', 'boolean'],
        ];
    }
}
