<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['country_id', 'depth', 'admin_level_id'])]
class CountryAdminStructure extends Model
{
    protected $primaryKey = null;

    public $incrementing = false;

    public $timestamps = false;

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
}
