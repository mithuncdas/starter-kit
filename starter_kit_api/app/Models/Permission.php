<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission as SpatiePermission;

#[Fillable(['name', 'guard_name', 'group'])]
class Permission extends SpatiePermission
{
    /** @param  Builder<self>  $query */
    public function scopeGrouped(Builder $query): void
    {
        $query->orderBy('group')->orderBy('name');
    }
}
