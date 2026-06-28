<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleStatusEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissionListTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
    }

    private function adminWithPermissions(array $names): User
    {
        $role = Role::query()->create([
            'name' => 'TestRole',
            'guard_name' => 'sanctum',
            'status' => RoleStatusEnum::Active,
        ]);
        $role->syncPermissions(Permission::query()->whereIn('name', $names)->get());

        $admin = User::factory()->admin()->create();
        $admin->syncRoles([$role]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }

    #[Test]
    public function permissions_endpoint_requires_roles_view_permission(): void
    {
        $admin = $this->adminWithPermissions([]);
        $token = $admin->createToken('device')->plainTextToken;

        $this->asToken($token)
            ->getJson('/api/admin/permissions')
            ->assertStatus(403);
    }

    #[Test]
    public function permissions_endpoint_returns_grouped_payload(): void
    {
        $admin = $this->adminWithPermissions(['roles.view']);
        $token = $admin->createToken('device')->plainTextToken;

        $response = $this->asToken($token)
            ->getJson('/api/admin/permissions');

        $response->assertOk()
            ->assertJsonStructure([
                'success', 'message',
                'data' => [
                    ['group', 'permissions' => [['id', 'name']]],
                ],
            ]);

        $groups = collect($response->json('data'))->pluck('group')->all();
        $this->assertSame(['Admins', 'Audit', 'Locations', 'Roles', 'Users'], $groups, 'Groups should be alphabetical.');

        $rolesGroup = collect($response->json('data'))->firstWhere('group', 'Roles');
        $this->assertCount(4, $rolesGroup['permissions']);
        $names = collect($rolesGroup['permissions'])->pluck('name')->all();
        $this->assertSame(
            ['roles.create', 'roles.delete', 'roles.update', 'roles.view'],
            $names,
        );
    }

    #[Test]
    public function permission_seeder_is_idempotent(): void
    {
        $before = Permission::query()->count();
        $this->seed(PermissionSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->assertSame($before, Permission::query()->count());
    }
}
