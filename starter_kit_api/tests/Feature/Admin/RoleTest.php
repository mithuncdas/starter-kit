<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleStatusEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\CreatesAdmins;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use CreatesAdmins, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    #[Test]
    public function listing_roles_requires_roles_view_permission(): void
    {
        $admin = $this->adminWithPermissions([]);

        $this->asToken($this->tokenFor($admin))
            ->getJson('/api/admin/roles')
            ->assertStatus(403);
    }

    #[Test]
    public function listing_roles_returns_paginated_data_with_permissions(): void
    {
        $admin = $this->adminWithPermissions(['roles.view']);

        $response = $this->asToken($this->tokenFor($admin))
            ->getJson('/api/admin/roles?per_page=15');

        $response->assertOk()
            ->assertJsonStructure([
                'success', 'message',
                'data' => [
                    'data' => [
                        ['id', 'name', 'status', 'status_label', 'permissions'],
                    ],
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ])
            ->assertJsonPath('data.meta.per_page', 15);
    }

    #[Test]
    public function listing_roles_without_per_page_uses_default_page_size(): void
    {
        $admin = $this->adminWithPermissions(['roles.view']);

        $response = $this->asToken($this->tokenFor($admin))
            ->getJson('/api/admin/roles');

        $response->assertOk()
            ->assertJsonStructure([
                'success', 'message',
                'data' => [
                    'data' => [
                        ['id', 'name', 'status', 'status_label', 'permissions'],
                    ],
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ])
            ->assertJsonPath('data.meta.per_page', 10);
    }

    #[Test]
    public function listing_roles_rejects_invalid_query_params(): void
    {
        $admin = $this->adminWithPermissions(['roles.view']);
        $token = $this->tokenFor($admin);

        $this->asToken($token)->getJson('/api/admin/roles?per_page=999')
            ->assertStatus(422)->assertJsonValidationErrors(['per_page']);

        $this->asToken($token)->getJson('/api/admin/roles?per_page=0')
            ->assertStatus(422)->assertJsonValidationErrors(['per_page']);

        $this->asToken($token)->getJson('/api/admin/roles?per_page=abc')
            ->assertStatus(422)->assertJsonValidationErrors(['per_page']);

        $this->asToken($token)->getJson('/api/admin/roles?status=42')
            ->assertStatus(422)->assertJsonValidationErrors(['status']);

        $this->asToken($token)->getJson('/api/admin/roles?name='.str_repeat('a', 121))
            ->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function create_role_persists_name_status_and_syncs_permissions(): void
    {
        $admin = $this->adminWithPermissions(['roles.create']);
        $permissions = Permission::query()->limit(3)->pluck('id')->all();

        $response = $this->asToken($this->tokenFor($admin))
            ->postJson('/api/admin/roles', [
                'name' => 'Manager',
                'status' => RoleStatusEnum::Active->value,
                'permissions' => $permissions,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Manager')
            ->assertJsonPath('data.status', RoleStatusEnum::Active->value);

        $role = Role::query()->where('name', 'Manager')->firstOrFail();
        $this->assertSame(3, $role->permissions()->count());
    }

    #[Test]
    public function create_role_rejects_duplicate_name(): void
    {
        $admin = $this->adminWithPermissions(['roles.create']);
        Role::query()->create(['name' => 'Manager', 'guard_name' => 'sanctum', 'status' => RoleStatusEnum::Active]);

        $this->asToken($this->tokenFor($admin))
            ->postJson('/api/admin/roles', [
                'name' => 'Manager',
                'status' => RoleStatusEnum::Active->value,
                'permissions' => Permission::query()->limit(1)->pluck('id')->all(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    #[Test]
    public function create_role_rejects_empty_permissions(): void
    {
        $admin = $this->adminWithPermissions(['roles.create']);

        $this->asToken($this->tokenFor($admin))
            ->postJson('/api/admin/roles', [
                'name' => 'Manager',
                'status' => RoleStatusEnum::Active->value,
                'permissions' => [],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['permissions']);
    }

    #[Test]
    public function update_role_rejects_when_actor_is_assigned_to_that_role(): void
    {
        $admin = $this->adminWithPermissions(['roles.update'], roleName: 'OwnedRole');
        $own = Role::query()->where('name', 'OwnedRole')->firstOrFail();

        $response = $this->asToken($this->tokenFor($admin))
            ->putJson("/api/admin/roles/{$own->id}", [
                'name' => 'Renamed',
                'status' => RoleStatusEnum::Active->value,
                'permissions' => Permission::query()->limit(2)->pluck('id')->all(),
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'You cannot edit the role currently assigned to you.');
    }

    #[Test]
    public function update_role_succeeds_for_role_not_assigned_to_actor(): void
    {
        $admin = $this->adminWithPermissions(['roles.update']);
        $target = Role::query()->create(['name' => 'Editor', 'guard_name' => 'sanctum', 'status' => RoleStatusEnum::Active]);
        $newPerms = Permission::query()->limit(2)->pluck('id')->all();

        $response = $this->asToken($this->tokenFor($admin))
            ->putJson("/api/admin/roles/{$target->id}", [
                'name' => 'Editor Updated',
                'status' => RoleStatusEnum::Inactive->value,
                'permissions' => $newPerms,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Editor Updated')
            ->assertJsonPath('data.status', RoleStatusEnum::Inactive->value);
        $this->assertSame(2, $target->fresh()->permissions()->count());
    }

    #[Test]
    public function delete_role_rejects_when_role_is_attached_to_any_user(): void
    {
        $admin = $this->adminWithPermissions(['roles.delete']);
        $target = Role::query()->create(['name' => 'Doomed', 'guard_name' => 'sanctum', 'status' => RoleStatusEnum::Active]);

        $other = User::factory()->admin()->create();
        $other->syncRoles([$target]);

        $response = $this->asToken($this->tokenFor($admin))
            ->deleteJson("/api/admin/roles/{$target->id}");

        $response->assertStatus(422);
        $this->assertStringContainsString('currently assigned', $response->json('message'));
        $this->assertNotNull(Role::query()->find($target->id));
    }

    #[Test]
    public function delete_role_rejects_when_actor_holds_that_role(): void
    {
        $admin = $this->adminWithPermissions(['roles.delete'], roleName: 'OwnedRole');
        $own = Role::query()->where('name', 'OwnedRole')->firstOrFail();

        $this->asToken($this->tokenFor($admin))
            ->deleteJson("/api/admin/roles/{$own->id}")
            ->assertStatus(403)
            ->assertJsonPath('message', 'You cannot delete the role currently assigned to you.');
    }

    #[Test]
    public function delete_role_succeeds_after_reassigning_all_attached_admins(): void
    {
        $admin = $this->adminWithPermissions(['roles.delete']);
        $doomed = Role::query()->create(['name' => 'Doomed', 'guard_name' => 'sanctum', 'status' => RoleStatusEnum::Active]);
        $replacement = Role::query()->create(['name' => 'Replacement', 'guard_name' => 'sanctum', 'status' => RoleStatusEnum::Active]);

        $other = User::factory()->admin()->create();
        $other->syncRoles([$doomed]);
        $other->syncRoles([$replacement]);

        $this->asToken($this->tokenFor($admin))
            ->deleteJson("/api/admin/roles/{$doomed->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Role deleted.');

        $this->assertNull(Role::query()->find($doomed->id));
    }

    #[Test]
    public function updating_a_role_invalidates_permission_cache_for_attached_admin(): void
    {
        $admin = $this->adminWithPermissions(['roles.update']);

        $other = User::factory()->admin()->create();
        $target = Role::query()->create(['name' => 'Editor', 'guard_name' => 'sanctum', 'status' => RoleStatusEnum::Active]);
        $other->syncRoles([$target]);
        $target->syncPermissions(Permission::query()->where('name', 'roles.view')->get());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $otherToken = $this->tokenFor($other);

        $this->asToken($otherToken)
            ->getJson('/api/admin/roles')
            ->assertOk();

        $newPermsList = Permission::query()->where('name', 'users.view')->pluck('id')->all();
        $this->asToken($this->tokenFor($admin))
            ->putJson("/api/admin/roles/{$target->id}", [
                'name' => 'Editor',
                'status' => RoleStatusEnum::Active->value,
                'permissions' => $newPermsList,
            ])
            ->assertOk();

        $this->asToken($otherToken)
            ->getJson('/api/admin/roles')
            ->assertStatus(403);
    }

    #[Test]
    public function show_role_returns_404_for_unknown_id(): void
    {
        $admin = $this->adminWithPermissions(['roles.view']);

        $this->asToken($this->tokenFor($admin))
            ->getJson('/api/admin/roles/999999')
            ->assertStatus(404);
    }

    #[Test]
    public function update_role_returns_404_for_unknown_id(): void
    {
        $admin = $this->adminWithPermissions(['roles.update']);

        $this->asToken($this->tokenFor($admin))
            ->putJson('/api/admin/roles/999999', [
                'name' => 'Nope',
                'status' => RoleStatusEnum::Active->value,
                'permissions' => [],
            ])
            ->assertStatus(404);
    }

    #[Test]
    public function delete_role_returns_404_for_unknown_id(): void
    {
        $admin = $this->adminWithPermissions(['roles.delete']);

        $this->asToken($this->tokenFor($admin))
            ->deleteJson('/api/admin/roles/999999')
            ->assertStatus(404);
    }
}
