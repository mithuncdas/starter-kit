<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleStatusEnum;
use App\Enums\UserStatusEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\CreatesAdmins;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use CreatesAdmins, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    private function activeRole(string $name = 'Editor'): Role
    {
        return Role::query()->create([
            'name' => $name,
            'guard_name' => 'sanctum',
            'status' => RoleStatusEnum::Active,
        ]);
    }

    #[Test]
    public function list_admins_requires_admins_view(): void
    {
        $admin = $this->adminWithPermissions([]);

        $this->asToken($this->tokenFor($admin))
            ->getJson('/api/admin/admin-users')
            ->assertStatus(403);
    }

    #[Test]
    public function list_admins_returns_paginated_data(): void
    {
        $admin = $this->adminWithPermissions(['admins.view']);
        User::factory()->admin()->count(2)->create();

        $response = $this->asToken($this->tokenFor($admin))
            ->getJson('/api/admin/admin-users?per_page=15');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'data' => [['id', 'name', 'email', 'status', 'role', 'permissions']],
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ])
            ->assertJsonPath('data.meta.per_page', 15);
    }

    #[Test]
    public function list_admins_without_per_page_uses_default_page_size(): void
    {
        $admin = $this->adminWithPermissions(['admins.view']);
        User::factory()->admin()->count(2)->create();

        $response = $this->asToken($this->tokenFor($admin))
            ->getJson('/api/admin/admin-users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'data' => [['id', 'name', 'email', 'status', 'role', 'permissions']],
                    'meta' => ['current_page', 'last_page', 'per_page', 'total'],
                ],
            ])
            ->assertJsonPath('data.meta.per_page', 10);
    }

    #[Test]
    public function create_admin_sets_user_type_admin_and_assigns_role(): void
    {
        $admin = $this->adminWithPermissions(['admins.create']);
        $role = $this->activeRole();

        $response = $this->asToken($this->tokenFor($admin))
            ->postJson('/api/admin/admin-users', [
                'name' => 'New Admin',
                'email' => 'new-admin@example.com',
                'password' => 'Password!123',
                'password_confirmation' => 'Password!123',
                'status' => UserStatusEnum::Active->value,
                'role_id' => $role->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.email', 'new-admin@example.com')
            ->assertJsonPath('data.role.id', $role->id);

        $created = User::query()->where('email', 'new-admin@example.com')->firstOrFail();
        $this->assertTrue($created->isAdmin());
        $this->assertTrue($created->hasRole($role));
    }

    #[Test]
    public function create_admin_rejects_duplicate_email(): void
    {
        $admin = $this->adminWithPermissions(['admins.create']);
        $role = $this->activeRole();
        User::factory()->create(['email' => 'taken@example.com']);

        $this->asToken($this->tokenFor($admin))
            ->postJson('/api/admin/admin-users', [
                'name' => 'X',
                'email' => 'taken@example.com',
                'password' => 'Password!123',
                'password_confirmation' => 'Password!123',
                'status' => UserStatusEnum::Active->value,
                'role_id' => $role->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function create_admin_rejects_deactivated_role(): void
    {
        $admin = $this->adminWithPermissions(['admins.create']);
        $deactivated = Role::query()->create([
            'name' => 'OffRole',
            'guard_name' => 'sanctum',
            'status' => RoleStatusEnum::Inactive,
        ]);

        $this->asToken($this->tokenFor($admin))
            ->postJson('/api/admin/admin-users', [
                'name' => 'X',
                'email' => 'fresh@example.com',
                'password' => 'Password!123',
                'password_confirmation' => 'Password!123',
                'status' => UserStatusEnum::Active->value,
                'role_id' => $deactivated->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role_id']);
    }

    #[Test]
    public function show_admin_returns_role_and_flat_permissions(): void
    {
        $admin = $this->adminWithPermissions(['admins.view']);
        $target = User::factory()->admin()->create();
        $role = $this->activeRole();
        $role->syncPermissions(Permission::query()->whereIn('name', ['users.view', 'users.update'])->get());
        $target->syncRoles([$role]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $response = $this->asToken($this->tokenFor($admin))
            ->getJson("/api/admin/admin-users/{$target->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $target->id)
            ->assertJsonPath('data.role.id', $role->id);

        $this->assertEqualsCanonicalizing(
            ['users.view', 'users.update'],
            $response->json('data.permissions'),
        );
    }

    #[Test]
    public function update_admin_rejects_when_target_is_self(): void
    {
        $admin = $this->adminWithPermissions(['admins.update']);
        $role = $this->activeRole();

        $this->asToken($this->tokenFor($admin))
            ->putJson("/api/admin/admin-users/{$admin->id}", [
                'name' => 'Self Renamed',
                'email' => $admin->email,
                'status' => UserStatusEnum::Active->value,
                'role_id' => $role->id,
            ])
            ->assertStatus(403)
            ->assertJsonPath('message', 'You cannot edit your own admin account.');
    }

    #[Test]
    public function update_admin_succeeds_for_a_different_admin(): void
    {
        $admin = $this->adminWithPermissions(['admins.update']);
        $target = User::factory()->admin()->create();
        $role = $this->activeRole();

        $response = $this->asToken($this->tokenFor($admin))
            ->putJson("/api/admin/admin-users/{$target->id}", [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'status' => UserStatusEnum::Active->value,
                'role_id' => $role->id,
            ]);

        $response->assertOk()->assertJsonPath('data.name', 'Updated Name');
        $this->assertTrue($target->fresh()->hasRole($role));
    }

    #[Test]
    public function delete_admin_rejects_when_target_is_self(): void
    {
        $admin = $this->adminWithPermissions(['admins.delete']);

        $this->asToken($this->tokenFor($admin))
            ->deleteJson("/api/admin/admin-users/{$admin->id}")
            ->assertStatus(403)
            ->assertJsonPath('message', 'You cannot delete your own admin account.');
    }

    #[Test]
    public function delete_admin_revokes_sanctum_tokens_of_target(): void
    {
        $admin = $this->adminWithPermissions(['admins.delete']);
        $target = User::factory()->admin()->create();
        $targetToken = $this->tokenFor($target);
        $this->assertSame(1, $target->tokens()->count());

        $this->asToken($this->tokenFor($admin))
            ->deleteJson("/api/admin/admin-users/{$target->id}")
            ->assertOk();

        $this->assertNull(User::query()->find($target->id));
        $this->assertSame(
            0,
            PersonalAccessToken::query()
                ->where('tokenable_id', $target->id)
                ->where('tokenable_type', User::class)
                ->count(),
            'Target tokens should have been revoked.',
        );

        $this->asToken($targetToken)
            ->getJson('/api/admin/profile')
            ->assertStatus(401);
    }

    #[Test]
    public function admin_without_required_permission_gets_json_403(): void
    {
        $admin = $this->adminWithPermissions(['users.view']);

        $this->asToken($this->tokenFor($admin))
            ->getJson('/api/admin/admin-users')
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You do not have permission to perform this action.');
    }

    #[Test]
    public function regular_user_token_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create();

        $this->asToken($this->tokenFor($user))
            ->getJson('/api/admin/admin-users')
            ->assertStatus(403);
    }

    #[Test]
    public function index_rejects_invalid_query_params(): void
    {
        $admin = $this->adminWithPermissions(['admins.view']);
        $token = $this->tokenFor($admin);

        $this->asToken($token)->getJson('/api/admin/admin-users?per_page=999')
            ->assertStatus(422)->assertJsonValidationErrors(['per_page']);

        $this->asToken($token)->getJson('/api/admin/admin-users?per_page=0')
            ->assertStatus(422)->assertJsonValidationErrors(['per_page']);

        $this->asToken($token)->getJson('/api/admin/admin-users?per_page=abc')
            ->assertStatus(422)->assertJsonValidationErrors(['per_page']);

        $this->asToken($token)->getJson('/api/admin/admin-users?status=42')
            ->assertStatus(422)->assertJsonValidationErrors(['status']);

        $this->asToken($token)->getJson('/api/admin/admin-users?role_id=999999')
            ->assertStatus(422)->assertJsonValidationErrors(['role_id']);

        $this->asToken($token)->getJson('/api/admin/admin-users?search='.str_repeat('a', 121))
            ->assertStatus(422)->assertJsonValidationErrors(['search']);
    }

    #[Test]
    public function create_admin_user_rejects_email_longer_than_255(): void
    {
        $admin = $this->adminWithPermissions(['admins.create']);
        $role = $this->activeRole('Editor');
        // 246 chars of local part + '@example.com' (12 chars) = 258 — over the 255 max.
        $longLocal = str_repeat('a', 246);

        $this->asToken($this->tokenFor($admin))
            ->postJson('/api/admin/admin-users', [
                'name' => 'Tall Email',
                'email' => "{$longLocal}@example.com",
                'password' => 'StrongPass!123A',
                'password_confirmation' => 'StrongPass!123A',
                'status' => UserStatusEnum::Active->value,
                'role_id' => $role->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function create_admin_user_rejects_weak_password(): void
    {
        $admin = $this->adminWithPermissions(['admins.create']);
        $role = $this->activeRole('Editor');

        $this->asToken($this->tokenFor($admin))
            ->postJson('/api/admin/admin-users', [
                'name' => 'New Admin',
                'email' => 'new-admin@example.com',
                'password' => 'weakpass',
                'password_confirmation' => 'weakpass',
                'status' => UserStatusEnum::Active->value,
                'role_id' => $role->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function update_admin_user_rejects_weak_password_when_provided(): void
    {
        $admin = $this->adminWithPermissions(['admins.update']);
        $target = User::factory()->admin()->create();
        $role = $this->activeRole('Editor');
        $target->syncRoles([$role]);

        $this->asToken($this->tokenFor($admin))
            ->putJson("/api/admin/admin-users/{$target->id}", [
                'name' => $target->name,
                'email' => $target->email,
                'password' => 'weakpass',
                'password_confirmation' => 'weakpass',
                'status' => UserStatusEnum::Active->value,
                'role_id' => $role->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function show_admin_user_returns_404_for_unknown_id(): void
    {
        $admin = $this->adminWithPermissions(['admins.view']);

        $this->asToken($this->tokenFor($admin))
            ->getJson('/api/admin/admin-users/999999')
            ->assertStatus(404);
    }

    #[Test]
    public function update_admin_user_returns_404_for_unknown_id(): void
    {
        $admin = $this->adminWithPermissions(['admins.update']);
        $role = $this->activeRole('Some');

        $this->asToken($this->tokenFor($admin))
            ->putJson('/api/admin/admin-users/999999', [
                'name' => 'Ghost',
                'email' => 'ghost@example.com',
                'status' => UserStatusEnum::Active->value,
                'role_id' => $role->id,
            ])
            ->assertStatus(404);
    }

    #[Test]
    public function delete_admin_user_returns_404_for_unknown_id(): void
    {
        $admin = $this->adminWithPermissions(['admins.delete']);

        $this->asToken($this->tokenFor($admin))
            ->deleteJson('/api/admin/admin-users/999999')
            ->assertStatus(404);
    }
}
