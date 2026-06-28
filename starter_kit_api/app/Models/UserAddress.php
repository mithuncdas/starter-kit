<?php

namespace App\Models;

use App\Enums\UserAddressLabelEnum;
use Database\Factories\UserAddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'admin_area_id',
    'label',
    'is_primary',
    'address_line1',
    'address_line2',
    'latitude',
    'longitude',
    'notes',
])]
class UserAddress extends Model
{
    /** @use HasFactory<UserAddressFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'label' => UserAddressLabelEnum::class,
            'is_primary' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<AdminArea, $this> */
    public function adminArea(): BelongsTo
    {
        return $this->belongsTo(AdminArea::class);
    }
}
