<?php

namespace App\Http\Resources\Admin;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Country
 */
class CountryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'iso2' => $this->iso2,
            'iso3' => $this->iso3,
            'name' => $this->name,
            'isd_prefix' => $this->isd_prefix,
            'default_timezone' => $this->default_timezone,
            'is_active' => $this->is_active,
            'structure' => $this->whenLoaded('structure', fn () => $this->structure->map(fn ($row) => [
                'depth' => $row->depth,
                'level' => $row->level?->code,
                'label' => $row->level?->name,
            ])->values()),
        ];
    }
}
