<?php

namespace App\Http\Resources\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class AdminResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'user_type' => $this->user_type->value,
            'user_type_label' => $this->user_type->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'email_verified_at' => $this->email_verified_at?->format('d/m/Y h:i A'),
            'created_at' => $this->created_at?->format('d/m/Y h:i A'),
            'roles' => $this->roles->map(fn (Role $role) => [
                'name' => $role->name,
                'status_label' => $role->status->label(),
            ])->values(),
            'permissions' => $this->when(
                $this->relationLoaded('roles') && $this->relationLoaded('permissions'),
                fn () => $this->getAllPermissions()->pluck('name')->values(),
            ),
        ];
    }
}
