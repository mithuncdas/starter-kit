<?php

namespace App\Models;

use App\Enums\RoleStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role as SpatieRole;

#[Fillable(['name', 'guard_name', 'status'])]
class Role extends SpatieRole
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RoleStatusEnum::class,
        ];
    }

    public function isActive(): bool
    {
        return $this->status === RoleStatusEnum::Active;
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', RoleStatusEnum::Active);
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%'.$filters['name'].'%');
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $status = (int) $filters['status'];
            if (in_array($status, RoleStatusEnum::values(), true)) {
                $query->where('status', $status);
            }
        }
    }
}
