<?php

namespace App\Http\Resources\Admin;

use App\Models\AdminArea;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AdminArea
 */
class AdminAreaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'country_id' => $this->country_id,
            'level' => $this->whenLoaded('level', fn () => $this->level?->code),
            'level_label' => $this->whenLoaded('level', fn () => $this->level?->name),
            'depth' => $this->depth,
            'code' => $this->code,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timezone' => $this->timezone,
            'is_active' => $this->is_active,
        ];
    }
}
