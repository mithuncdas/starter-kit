<?php

namespace App\Http\Requests\Admin\UserAddress;

use App\Enums\UserAddressLabelEnum;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreUserAddressRequest extends FormRequest
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
            'admin_area_id' => ['required', 'integer', Rule::exists('admin_areas', 'id')->where('is_active', true)],
            'label' => ['required', 'integer', new Enum(UserAddressLabelEnum::class)],
            'is_primary' => ['sometimes', 'boolean'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
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
