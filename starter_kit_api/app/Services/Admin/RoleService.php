<?php

namespace App\Services\Admin;

use App\Enums\RoleStatusEnum;
use App\Exceptions\CannotModifyOwnRoleException;
use App\Exceptions\RoleStillInUseException;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Chronicle\Facades\Chronicle;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class RoleService
{
    public function __construct(protected PermissionRegistrar $registrar) {}

    /**
     * @param  array{name: string, status: int, permissions: array<int, int>}  $data
     */
    public function create(array $data, User $actor): Role
    {
        $role = DB::transaction(function () use ($data): Role {
            $role = Role::query()->create([
                'name' => $data['name'],
                'guard_name' => 'sanctum',
                'status' => RoleStatusEnum::from($data['status']),
            ]);

            $role->syncPermissions(Permission::query()->whereIn('id', $data['permissions'])->get());

            return $role;
        });

        $this->registrar->forgetCachedPermissions();

        $role->load('permissions');

        Chronicle::record()
            ->actor($actor)
            ->action('role.created')
            ->subject($role)
            ->metadata([
                'name' => $role->name,
                'status' => $role->status->value,
                'permission_ids' => $role->permissions->pluck('id')->all(),
                'permission_names' => $role->permissions->pluck('name')->all(),
            ])
            ->tags(['rbac'])
            ->commit();

        return $role;
    }

    /**
     * @param  array{name: string, status: int, permissions: array<int, int>}  $data
     *
     * @throws CannotModifyOwnRoleException
     */
    public function update(Role $role, array $data, User $actor): Role
    {
        if ($actor->hasRole($role->name)) {
            throw new CannotModifyOwnRoleException(
                'You cannot edit the role currently assigned to you.'
            );
        }

        $changes = DB::transaction(function () use ($role, $data): array {
            $originalName = $role->name;
            $originalStatus = $role->status;
            $originalPermissionIds = $role->permissions->pluck('id')->all();

            $role->fill([
                'name' => $data['name'],
                'status' => RoleStatusEnum::from($data['status']),
            ])->save();

            $role->syncPermissions(Permission::query()->whereIn('id', $data['permissions'])->get());

            $role->load('permissions');
            $newPermissionIds = $role->permissions->pluck('id')->all();

            $fieldDiff = [];
            if ($originalName !== $role->name) {
                $fieldDiff['name'] = ['old' => $originalName, 'new' => $role->name];
            }
            if ($originalStatus !== $role->status) {
                $fieldDiff['status'] = ['old' => $originalStatus->value, 'new' => $role->status->value];
            }

            $added = array_values(array_diff($newPermissionIds, $originalPermissionIds));
            $removed = array_values(array_diff($originalPermissionIds, $newPermissionIds));

            return [
                'field_diff' => $fieldDiff,
                'permissions_added' => $added,
                'permissions_removed' => $removed,
            ];
        });

        $this->registrar->forgetCachedPermissions();

        if (! empty($changes['field_diff'])) {
            Chronicle::record()
                ->actor($actor)
                ->action('role.updated')
                ->subject($role)
                ->diff($changes['field_diff'])
                ->tags(['rbac'])
                ->commit();
        }

        if (! empty($changes['permissions_added']) || ! empty($changes['permissions_removed'])) {
            Chronicle::record()
                ->actor($actor)
                ->action('role.permissions_synced')
                ->subject($role)
                ->metadata([
                    'added' => $changes['permissions_added'],
                    'removed' => $changes['permissions_removed'],
                ])
                ->tags(['rbac', 'security'])
                ->commit();
        }

        return $role->fresh(['permissions']);
    }

    /**
     * @throws CannotModifyOwnRoleException
     * @throws RoleStillInUseException
     */
    public function delete(Role $role, User $actor): void
    {
        if ($actor->hasRole($role->name)) {
            throw new CannotModifyOwnRoleException(
                'You cannot delete the role currently assigned to you.'
            );
        }

        $assignedCount = $role->users()->count();

        if ($assignedCount > 0) {
            throw new RoleStillInUseException(
                "This role is currently assigned to {$assignedCount} admin(s). Reassign them before deleting."
            );
        }

        $snapshot = [
            'name' => $role->name,
            'status' => $role->status->value,
            'permission_ids' => $role->permissions->pluck('id')->all(),
        ];

        $role->delete();

        $this->registrar->forgetCachedPermissions();

        Chronicle::record()
            ->actor($actor)
            ->action('role.deleted')
            ->subject($role)
            ->metadata($snapshot)
            ->tags(['rbac'])
            ->commit();
    }
}
