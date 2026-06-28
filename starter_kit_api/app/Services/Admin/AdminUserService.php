<?php

namespace App\Services\Admin;

use App\Enums\UserStatusEnum;
use App\Enums\UserTypeEnum;
use App\Exceptions\CannotModifySelfException;
use App\Models\Role;
use App\Models\User;
use Chronicle\Facades\Chronicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserService
{
    /**
     * @param  array{name: string, email: string, password: string, status: int, role_id: int}  $data
     */
    public function create(array $data, User $actor): User
    {
        $result = DB::transaction(function () use ($data): array {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'user_type' => UserTypeEnum::Admin,
                'status' => UserStatusEnum::from($data['status']),
                'email_verified_at' => now(),
            ]);

            $role = Role::query()->findOrFail($data['role_id']);
            $user->syncRoles([$role]);

            return [
                'user' => $user->load(['roles.permissions', 'permissions']),
                'role' => $role,
            ];
        });

        $user = $result['user'];
        $role = $result['role'];

        Chronicle::record()
            ->actor($actor)
            ->action('admin_user.created')
            ->subject($user)
            ->metadata([
                'role_id' => $role->id,
                'role_name' => $role->name,
            ])
            ->diff([
                'name' => ['old' => null, 'new' => $user->name],
                'email' => ['old' => null, 'new' => $user->email],
                'status' => ['old' => null, 'new' => $user->status->value],
            ])
            ->tags(['admin', 'rbac'])
            ->commit();

        return $user;
    }

    /**
     * @param  array{name: string, email: string, password: ?string, status: int, role_id: int}  $data
     *
     * @throws CannotModifySelfException
     */
    public function update(User $target, array $data, User $actor): User
    {
        if ($target->id === $actor->id) {
            throw new CannotModifySelfException(
                'You cannot edit your own admin account.'
            );
        }

        $changes = DB::transaction(function () use ($target, $data): array {
            $target->loadMissing('roles');

            $originalName = $target->name;
            $originalEmail = $target->email;
            $originalStatus = $target->status;
            $originalRole = $target->roles->first();

            $attributes = [
                'name' => $data['name'],
                'email' => $data['email'],
                'status' => UserStatusEnum::from($data['status']),
            ];

            $passwordChanged = ! empty($data['password']);

            if ($passwordChanged) {
                $attributes['password'] = Hash::make($data['password']);
            }

            $target->forceFill($attributes)->save();

            $role = Role::query()->findOrFail($data['role_id']);
            $target->syncRoles([$role]);

            $statusChanged = $originalStatus !== $target->status;
            $tokensRevoked = 0;

            if ($passwordChanged || $statusChanged) {
                $tokensRevoked = $target->tokens()->count();
                $target->tokens()->delete();
            }

            $fieldDiff = [];
            if ($originalName !== $target->name) {
                $fieldDiff['name'] = ['old' => $originalName, 'new' => $target->name];
            }
            if ($originalEmail !== $target->email) {
                $fieldDiff['email'] = ['old' => $originalEmail, 'new' => $target->email];
            }
            if ($originalStatus !== $target->status) {
                $fieldDiff['status'] = ['old' => $originalStatus->value, 'new' => $target->status->value];
            }

            return [
                'role_changed' => $originalRole?->id !== $role->id,
                'old_role' => $originalRole,
                'new_role' => $role,
                'status_changed' => $statusChanged,
                'old_status' => $originalStatus,
                'new_status' => $target->status,
                'password_changed' => $passwordChanged,
                'tokens_revoked' => $tokensRevoked,
                'field_diff' => $fieldDiff,
            ];
        });

        $actorRef = $actor;

        if (! empty($changes['field_diff']) || $changes['password_changed']) {
            $builder = Chronicle::record()
                ->actor($actorRef)
                ->action('admin_user.updated')
                ->subject($target)
                ->metadata([
                    'password_changed' => $changes['password_changed'],
                    'tokens_revoked' => $changes['tokens_revoked'],
                ])
                ->tags(['admin']);

            if (! empty($changes['field_diff'])) {
                $builder->diff($changes['field_diff']);
            }

            $builder->commit();
        }

        if ($changes['role_changed']) {
            Chronicle::record()
                ->actor($actorRef)
                ->action('admin_user.role_changed')
                ->subject($target)
                ->diff([
                    'role' => [
                        'old' => $changes['old_role']?->name,
                        'new' => $changes['new_role']->name,
                    ],
                ])
                ->metadata([
                    'old_role_id' => $changes['old_role']?->id,
                    'new_role_id' => $changes['new_role']->id,
                ])
                ->tags(['admin', 'rbac'])
                ->commit();
        }

        if ($changes['status_changed']) {
            Chronicle::record()
                ->actor($actorRef)
                ->action('admin_user.status_changed')
                ->subject($target)
                ->diff([
                    'status' => [
                        'old' => $changes['old_status']->value,
                        'new' => $changes['new_status']->value,
                    ],
                ])
                ->tags(['admin', 'security'])
                ->commit();
        }

        return $target->fresh(['roles.permissions', 'permissions']);
    }

    /**
     * @throws CannotModifySelfException
     */
    public function delete(User $target, User $actor): void
    {
        if ($target->id === $actor->id) {
            throw new CannotModifySelfException(
                'You cannot delete your own admin account.'
            );
        }

        $target->loadMissing('roles');

        $snapshot = [
            'name' => $target->name,
            'email' => $target->email,
            'role_name' => $target->roles->first()?->name,
        ];

        $tokensRevoked = DB::transaction(function () use ($target): int {
            $count = $target->tokens()->count();
            $target->tokens()->delete();
            $target->delete();

            return $count;
        });

        Chronicle::record()
            ->actor($actor)
            ->action('admin_user.deleted')
            ->subject($target)
            ->metadata($snapshot + ['tokens_revoked' => $tokensRevoked])
            ->tags(['admin'])
            ->commit();
    }
}
