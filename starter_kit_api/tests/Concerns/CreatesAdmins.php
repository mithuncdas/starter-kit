<?php

namespace Tests\Concerns;

use App\Enums\RoleStatusEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;

trait CreatesAdmins
{
    /**
     * Build an admin user attached to a role that grants exactly the given permissions.
     * Re-uses the role if it already exists so callers can ramp up multiple admins on the same role.
     *
     * @param  list<string>  $permissionNames
     */
    protected function adminWithPermissions(array $permissionNames, string $roleName = 'TestRole'): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'sanctum'],
            ['status' => RoleStatusEnum::Active],
        );

        $role->syncPermissions(Permission::query()->whereIn('name', $permissionNames)->get());

        $admin = User::factory()->admin()->create();
        $admin->syncRoles([$role]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }

    protected function tokenFor(User $user, string $deviceName = 'test'): string
    {
        return $user->createToken($deviceName)->plainTextToken;
    }
}
