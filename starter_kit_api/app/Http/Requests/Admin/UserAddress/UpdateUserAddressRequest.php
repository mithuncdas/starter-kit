<?php

namespace App\Http\Requests\Admin\UserAddress;

use App\Enums\UserAddressLabelEnum;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateUserAddressRequest extends FormRequest
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
            'admin_area_id' => ['sometimes', 'integer', Rule::exists('admin_areas', 'id')->where('is_active', true)],
            'label' => ['sometimes', 'integer', new Enum(UserAddressLabelEnum::class)],
            'is_primary' => ['sometimes', 'boolean'],
            'address_line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if (! $this->has('latitude') && ! $this->has('longitude')) {
                return;
            }
            $lat = $this->input('latitude');
            $lon = $this->input('longitude');
            if (($lat === null) !== ($lon === null)) {
                $v->errors()->add('latitude', 'Latitude and longitude must be supplied together.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'admin_area_id.exists' => 'The selected location is invalid or inactive.',
        ];
    }
}
