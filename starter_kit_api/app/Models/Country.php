<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'iso2',
    'iso3',
    'name',
    'isd_prefix',
    'default_timezone',
    'is_active',
])]
class Country extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<AdminArea, $this> */
    public function adminAreas(): HasMany
    {
        return $this->hasMany(AdminArea::class);
    }

    /** @return HasMany<CountryAdminStructure, $this> */
    public function structure(): HasMany
    {
        return $this->hasMany(CountryAdminStructure::class)->orderBy('depth');
    }

    /** @return HasMany<AdminArea, $this> */
    public function topLevelAreas(): HasMany
    {
        return $this->hasMany(AdminArea::class)->whereNull('parent_id');
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
