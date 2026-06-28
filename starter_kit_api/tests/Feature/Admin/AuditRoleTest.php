<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleStatusEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Chronicle\Entry\Entry;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesAdmins;
use Tests\TestCase;

class AuditRoleTest extends TestCase
{
    use CreatesAdmins, LazilyRefreshDatabase;

    protected User $actor;

    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->actor = $this->adminWithPermissions(
            ['roles.view', 'roles.create', 'roles.update', 'roles.delete'],
            'RoleAdmin',
        );
        $this->token = $this->tokenFor($this->actor);
    }

    #[Test]
    public function creating_a_role_records_role_created_with_permission_snapshot(): void
    {
        $permissionIds = Permission::query()->whereIn('name', ['admins.view', 'admins.create'])->pluck('id')->all();

        $this->asToken($this->token)->postJson('/api/admin/roles', [
            'name' => 'Reviewer',
            'status' => RoleStatusEnum::Active->value,
            'permissions' => $permissionIds,
        ])->assertCreated();

        $role = Role::query()->where('name', 'Reviewer')->firstOrFail();

        $entry = Entry::query()->action('role.created')->forSubject($role)->first();
        $this->assertNotNull($entry);
        $this->assertSame('Reviewer', $entry->metadata['name']);
        $this->assertEqualsCanonicalizing($permissionIds, $entry->metadata['permission_ids']);
    }

    #[Test]
    public function role_field_change_records_role_updated_without_permissions_synced(): void
    {
        $role = Role::query()->create([
            'name' => 'Watcher',
            'guard_name' => 'sanctum',
            'status' => RoleStatusEnum::Active,
        ]);
        $perms = Permission::query()->where('name', 'admins.view')->pluck('id')->all();
        $role->syncPermissions(Permission::query()->whereIn('id', $perms)->get());

        $this->asToken($this->token)->putJson("/api/admin/roles/{$role->id}", [
            'name' => 'Observer',
            'status' => RoleStatusEnum::Active->value,
            'permissions' => $perms,
        ])->assertOk();

        $this->assertNotNull(Entry::query()->action('role.updated')->forSubject($role)->first());
        $this->assertNull(Entry::query()->action('role.permissions_synced')->forSubject($role)->first());
    }

    #[Test]
    public function permission_change_records_role_permissions_synced_with_added_and_removed(): void
    {
        $role = Role::query()->create([
            'name' => 'Manager2',
            'guard_name' => 'sanctum',
            'status' => RoleStatusEnum::Active,
        ]);

        $initial = Permission::query()->where('name', 'admins.view')->pluck('id')->all();
        $role->syncPermissions(Permission::query()->whereIn('id', $initial)->get());

        $target = Permission::query()->whereIn('name', ['admins.update', 'admins.delete'])->pluck('id')->all();

        $this->asToken($this->token)->putJson("/api/admin/roles/{$role->id}", [
            'name' => $role->name,
            'status' => RoleStatusEnum::Active->value,
            'permissions' => $target,
        ])->assertOk();

        $entry = Entry::query()->action('role.permissions_synced')->forSubject($role)->first();
        $this->assertNotNull($entry);
        $this->assertEqualsCanonicalizing($target, $entry->metadata['added']);
        $this->assertEqualsCanonicalizing($initial, $entry->metadata['removed']);
        $this->assertContains('security', $entry->tags);
    }

    #[Test]
    public function deleting_a_role_records_role_deleted(): void
    {
        $role = Role::query()->create([
            'name' => 'Disposable',
            'guard_name' => 'sanctum',
            'status' => RoleStatusEnum::Active,
        ]);

        $this->asToken($this->token)->deleteJson("/api/admin/roles/{$role->id}")->assertOk();

        $entry = Entry::query()->action('role.deleted')->where('subject_id', (string) $role->id)->first();
        $this->assertNotNull($entry);
        $this->assertSame('Disposable', $entry->metadata['name']);
    }
}
