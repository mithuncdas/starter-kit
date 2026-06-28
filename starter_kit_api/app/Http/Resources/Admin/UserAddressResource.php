<?php

namespace App\Http\Resources\Admin;

use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin UserAddress
 */
class UserAddressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded(
                'user',
                fn () => [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ],
            ),
            'admin_area_id' => $this->admin_area_id,
            'label' => $this->label?->value,
            'label_name' => $this->label?->label(),
            'is_primary' => $this->is_primary,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'notes' => $this->notes,
            'admin_area' => $this->whenLoaded(
                'adminArea',
                fn () => AdminAreaResource::make($this->adminArea)->resolve(),
            ),
            'hierarchy' => $this->when(
                $this->relationLoaded('adminArea')
                    && $this->adminArea !== null
                    && $this->adminArea->relationLoaded('ancestorsAndSelf'),
                fn () => $this->adminArea->ancestorsAndSelf
                    ->sortBy('depth')
                    ->values()
                    ->map(fn ($area) => [
                        'id' => $area->id,
                        'level' => $area->level?->code,
                        'level_label' => $area->level?->name,
                        'depth' => $area->depth,
                        'code' => $area->code,
                        'name' => $area->name,
                    ])
                    ->values(),
            ),
            'created_at' => $this->created_at?->format('d/m/Y h:i A'),
            'updated_at' => $this->updated_at?->format('d/m/Y h:i A'),
        ];
    }
}
