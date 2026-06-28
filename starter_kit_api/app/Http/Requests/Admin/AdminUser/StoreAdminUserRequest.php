<?php

namespace App\Http\Requests\Admin\AdminUser;

use App\Enums\RoleStatusEnum;
use App\Enums\UserStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'status' => ['required', 'integer', Rule::in(UserStatusEnum::values())],
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where('status', RoleStatusEnum::Active->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'role_id.exists' => 'The selected role is invalid or not active.',
        ];
    }
}
