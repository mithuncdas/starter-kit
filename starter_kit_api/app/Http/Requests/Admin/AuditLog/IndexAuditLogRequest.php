<?php

namespace App\Http\Requests\Admin\AuditLog;

use Illuminate\Foundation\Http\FormRequest;

class IndexAuditLogRequest extends FormRequest
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
            'actor_type' => ['nullable', 'string', 'max:255', 'required_with:actor_id'],
            'actor_id' => ['nullable', 'string', 'max:255', 'required_with:actor_type'],
            'subject_type' => ['nullable', 'string', 'max:255', 'required_with:subject_id'],
            'subject_id' => ['nullable', 'string', 'max:255', 'required_with:subject_type'],
        ];
    }
}
