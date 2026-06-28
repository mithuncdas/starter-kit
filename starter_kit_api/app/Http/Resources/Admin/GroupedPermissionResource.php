<?php

namespace App\Http\Resources\Admin;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Wraps a Collection<Permission> and emits them grouped by `group`.
 */
class GroupedPermissionResource extends JsonResource
{
    /**
     * @return array<int, array{group: string, permissions: array<int, array{id: int, name: string}>}>
     */
    public function toArray(Request $request): array
    {
        /** @var Collection<int, Permission> $permissions */
        $permissions = $this->resource;

        return $permissions
            ->sortBy(fn (Permission $permission) => [$permission->group, $permission->name])
            ->groupBy('group')
            ->map(fn (Collection $group, string $name): array => [
                'group' => $name,
                'permissions' => $group->map(fn (Permission $permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                ])->values()->all(),
            ])
            ->values()
            ->all();
    }
}
