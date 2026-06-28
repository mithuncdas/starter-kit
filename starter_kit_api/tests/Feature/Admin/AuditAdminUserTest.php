<?php

namespace Tests\Feature\Admin;

use App\Enums\RoleStatusEnum;
use App\Enums\UserStatusEnum;
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

class AuditAdminUserTest extends TestCase
{
    use CreatesAdmins, LazilyRefreshDatabase;

    protected User $actor;

    protected string $token;

    protected Role $managerRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $this->actor = $this->adminWithPermissions(
            ['admins.view', 'admins.create', 'admins.update', 'admins.delete'],
            'ActorRole',
        );
        $this->token = $this->tokenFor($this->actor);

        $this->managerRole = Role::query()->create([
            'name' => 'Manager',
            'guard_name' => 'sanctum',
            'status' => RoleStatusEnum::Active,
        ]);
        $this->managerRole->syncPermissions(Permission::query()->where('name', 'admins.view')->get());
    }

    #[Test]
    public function creating_an_admin_records_admin_user_created(): void
    {
        $this->asToken($this->token)->postJson('/api/admin/admin-users', [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'password' => 'StrongPassword1!',
            'password_confirmation' => 'StrongPassword1!',
            'status' => UserStatusEnum::Active->value,
            'role_id' => $this->managerRole->id,
        ])->assertCreated();

        $created = User::query()->where('email', 'newadmin@example.com')->firstOrFail();

        $entry = Entry::query()->action('admin_user.created')->forSubject($created)->first();
        $this->assertNotNull($entry);
        $this->assertSame($this->managerRole->id, $entry->metadata['role_id']);
        $this->assertSame('Manager', $entry->metadata['role_name']);
    }

    #[Test]
    public function updating_name_and_email_records_diff_in_admin_user_updated(): void
    {
        $target = User::factory()->admin()->create(['name' => 'Old', 'email' => 'old@example.com']);
        $target->syncRoles([$this->managerRole]);

        $this->asToken($this->token)->putJson("/api/admin/admin-users/{$target->id}", [
            'name' => 'New',
            'email' => 'new@example.com',
            'status' => UserStatusEnum::Active->value,
            'role_id' => $this->managerRole->id,
        ])->assertOk();

        $entry = Entry::query()->action('admin_user.updated')->forSubject($target)->first();
        $this->assertNotNull($entry);
        $this->assertSame('Old', $entry->diff['name']['old']);
        $this->assertSame('New', $entry->diff['name']['new']);
        $this->assertSame('old@example.com', $entry->diff['email']['old']);
        $this->assertSame('new@example.com', $entry->diff['email']['new']);
    }

    #[Test]
    public function role_swap_records_separate_role_changed_entry(): void
    {
        $otherRole = Role::query()->create([
            'name' => 'Auditor',
            'guard_name' => 'sanctum',
            'status' => RoleStatusEnum::Active,
        ]);

        $target = User::factory()->admin()->create();
        $target->syncRoles([$this->managerRole]);

        $this->asToken($this->token)->putJson("/api/admin/admin-users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'status' => UserStatusEnum::Active->value,
            'role_id' => $otherRole->id,
        ])->assertOk();

        $entry = Entry::query()->action('admin_user.role_changed')->forSubject($target)->first();
        $this->assertNotNull($entry);
        $this->assertSame('Manager', $entry->diff['role']['old']);
        $this->assertSame('Auditor', $entry->diff['role']['new']);
    }

    #[Test]
    public function status_flip_records_separate_status_changed_entry(): void
    {
        $target = User::factory()->admin()->create();
        $target->syncRoles([$this->managerRole]);

        $this->asToken($this->token)->putJson("/api/admin/admin-users/{$target->id}", [
            'name' => $target->name,
            'email' => $target->email,
            'status' => UserStatusEnum::Inactive->value,
            'role_id' => $this->managerRole->id,
        ])->assertOk();

        $entry = Entry::query()->action('admin_user.status_changed')->forSubject($target)->first();
        $this->assertNotNull($entry);
        $this->assertSame(UserStatusEnum::Active->value, $entry->diff['status']['old']);
        $this->assertSame(UserStatusEnum::Inactive->value, $entry->diff['status']['new']);
        $this->assertContains('security', $entry->tags);
    }

    #[Test]
    public function deleting_admin_records_admin_user_deleted_with_snapshot(): void
    {
        $target = User::factory()->admin()->create(['name' => 'To Be Deleted', 'email' => 'gone@example.com']);
        $target->syncRoles([$this->managerRole]);

        $this->asToken($this->token)->deleteJson("/api/admin/admin-users/{$target->id}")->assertOk();

        $entry = Entry::query()->action('admin_user.deleted')->where('subject_id', (string) $target->id)->first();
        $this->assertNotNull($entry);
        $this->assertSame('To Be Deleted', $entry->metadata['name']);
        $this->assertSame('gone@example.com', $entry->metadata['email']);
        $this->assertSame('Manager', $entry->metadata['role_name']);
    }
}
