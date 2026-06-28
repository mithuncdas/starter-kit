<?php

namespace App\Http\Resources\Admin;

use App\Models\AdminArea;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * @mixin AdminArea
 */
class AdminAreaTreeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Collection<int, AdminArea> $children */
        $children = $this->children ?? collect();

        return [
            'id' => $this->id,
            'level' => $this->level?->code,
            'level_label' => $this->level?->name,
            'code' => $this->code,
            'name' => $this->name,
            'depth' => $this->depth,
            'children' => self::collection($children),
        ];
    }
}
