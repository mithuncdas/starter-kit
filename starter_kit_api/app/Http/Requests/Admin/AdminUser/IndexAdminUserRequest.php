<?php

namespace App\Http\Requests\Admin\AdminUser;

use App\Enums\UserStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAdminUserRequest extends FormRequest
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
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'min:1', 'max:120'],
            'status' => ['sometimes', 'integer', Rule::in(UserStatusEnum::values())],
            'role_id' => ['sometimes', 'integer', Rule::exists('roles', 'id')],
        ];
    }
}
