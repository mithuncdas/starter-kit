<?php

namespace App\Http\Requests\Admin\Role;

use App\Enums\RoleStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexRoleRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'min:1', 'max:120'],
            'status' => ['sometimes', 'integer', Rule::in(RoleStatusEnum::values())],
        ];
    }
}
