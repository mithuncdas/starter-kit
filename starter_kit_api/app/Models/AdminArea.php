<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

#[Fillable([
    'country_id',
    'parent_id',
    'admin_level_id',
    'depth',
    'code',
    'name',
    'short_name',
    'latitude',
    'longitude',
    'timezone',
    'is_active',
])]
class AdminArea extends Model
{
    use HasRecursiveRelationships;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function getParentKeyName(): string
    {
        return 'parent_id';
    }

    public function getDepthName(): string
    {
        // CTE pseudo-column name. Must NOT be 'depth' because the table already has a column
        // called 'depth' (the stored hierarchical level), and the CTE would alias-clash with it.
        return 'tree_depth';
    }

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return BelongsTo<AdminLevel, $this> */
    public function level(): BelongsTo
    {
        return $this->belongsTo(AdminLevel::class, 'admin_level_id');
    }

    /** @param  Builder<self>  $query */
    public function scopeForCountry(Builder $query, int $countryId): void
    {
        $query->where('country_id', $countryId);
    }

    /** @param  Builder<self>  $query */
    public function scopeAtDepth(Builder $query, int $depth): void
    {
        $query->where('depth', $depth);
    }

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
