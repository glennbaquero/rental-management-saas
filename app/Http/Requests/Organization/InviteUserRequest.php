<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'   => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
                Rule::unique('user_invitations', 'email')->where('status', 'pending'),
            ],
            'role_id' => ['required', 'uuid', Rule::exists('roles', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email already has a pending invitation or is already a team member.',
        ];
    }
}
