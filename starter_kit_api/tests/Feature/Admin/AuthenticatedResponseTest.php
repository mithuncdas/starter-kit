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
use Tests\TestCase;

class AuthenticatedResponseTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    private function adminWithRole(?string $roleName, array $permissions = []): User
    {
        $admin = User::factory()->admin()->create();

        if ($roleName !== null) {
            $role = Role::query()->firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'sanctum'],
                ['status' => RoleStatusEnum::Active],
            );

            if ($permissions !== []) {
                $role->syncPermissions(Permission::query()->whereIn('name', $permissions)->get());
            } else {
                $role->syncPermissions([]);
            }

            $admin->syncRoles([$role]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return $admin;
    }

    #[Test]
    public function login_response_includes_roles_with_only_name_and_status_label(): void
    {
        $admin = $this->adminWithRole('Editor', ['users.view']);

        $response = $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertOk();
        $roles = $response->json('data.admin.roles');

        $this->assertCount(1, $roles);
        $this->assertSame(['name', 'status_label'], array_keys($roles[0]));
        $this->assertSame('Editor', $roles[0]['name']);
        $this->assertSame('Active', $roles[0]['status_label']);
    }

    #[Test]
    public function login_response_includes_permissions_as_flat_array_of_names(): void
    {
        $admin = $this->adminWithRole('Editor', ['users.view', 'users.update']);

        $response = $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertOk();
        $permissions = $response->json('data.admin.permissions');

        $this->assertIsArray($permissions);
        foreach ($permissions as $item) {
            $this->assertIsString($item);
        }
        $this->assertEqualsCanonicalizing(['users.view', 'users.update'], $permissions);
    }

    #[Test]
    public function admin_with_role_holding_every_permission_receives_full_catalog(): void
    {
        $admin = $this->adminWithRole('FullAccess', Permission::query()->pluck('name')->all());

        $response = $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertOk();
        $this->assertEqualsCanonicalizing(
            Permission::query()->pluck('name')->all(),
            $response->json('data.admin.permissions'),
        );
    }

    #[Test]
    public function admin_with_role_having_no_permissions_receives_empty_array(): void
    {
        $admin = $this->adminWithRole('NoPerms');

        $response = $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.admin.permissions', []);
    }

    #[Test]
    public function profile_returns_same_admin_resource_shape_as_login(): void
    {
        $admin = $this->adminWithRole('Editor', ['users.view']);
        $token = $admin->createToken('device')->plainTextToken;

        $profile = $this->asToken($token)
            ->getJson('/api/admin/profile');

        $profile->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'email', 'user_type', 'user_type_label', 'status', 'status_label',
                    'email_verified_at', 'created_at', 'roles', 'permissions',
                ],
            ]);

        $this->assertSame(['users.view'], $profile->json('data.permissions'));
        $this->assertSame('Editor', $profile->json('data.roles.0.name'));
    }

    #[Test]
    public function permission_cache_invalidation_reflects_on_next_profile_request(): void
    {
        $role = Role::query()->create([
            'name' => 'Editor',
            'guard_name' => 'sanctum',
            'status' => RoleStatusEnum::Active,
        ]);
        $role->syncPermissions(Permission::query()->where('name', 'users.view')->get());
        $admin = User::factory()->admin()->create();
        $admin->syncRoles([$role]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $token = $admin->createToken('device')->plainTextToken;

        $this->asToken($token)
            ->getJson('/api/admin/profile')
            ->assertOk()
            ->assertJsonPath('data.permissions', ['users.view']);

        $role->syncPermissions(Permission::query()->whereIn('name', ['users.view', 'users.update'])->get());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $names = $this->asToken($token)
            ->getJson('/api/admin/profile')
            ->assertOk()
            ->json('data.permissions');

        $this->assertEqualsCanonicalizing(['users.view', 'users.update'], $names);
    }
}
