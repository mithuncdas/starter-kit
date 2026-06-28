<?php

namespace Tests\Feature\Admin;

use App\Models\Otp;
use App\Models\User;
use App\Support\Audit\AnonymousLoginAttempt;
use Chronicle\Entry\Entry;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\Concerns\CreatesAdmins;
use Tests\TestCase;

class AuditAuthTest extends TestCase
{
    use CreatesAdmins, LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);
    }

    #[Test]
    public function successful_login_records_an_audit_entry(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'auditor@example.com']);

        $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
            'device_name' => 'unit-test',
        ])->assertOk();

        $entry = Entry::query()
            ->action('auth.login_succeeded')
            ->forSubject($admin)
            ->first();

        $this->assertNotNull($entry, 'auth.login.succeeded entry should exist');
        $this->assertSame('unit-test', $entry->metadata['device_name']);
        $this->assertContains('auth', $entry->tags);
    }

    #[Test]
    public function failed_login_for_known_user_records_invalid_credentials_entry(): void
    {
        $admin = User::factory()->admin()->create();

        $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ])->assertStatus(422);

        $entry = Entry::query()
            ->action('auth.login_failed')
            ->forSubject($admin)
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('invalid_credentials', $entry->metadata['reason']);
        $this->assertContains('security', $entry->tags);
    }

    #[Test]
    public function failed_login_for_unknown_email_records_anonymous_attempt(): void
    {
        $this->postJson('/api/admin/login', [
            'email' => 'ghost@example.com',
            'password' => 'whatever',
        ])->assertStatus(422);

        $entry = Entry::query()
            ->action('auth.login_failed')
            ->where('subject_type', AnonymousLoginAttempt::class)
            ->where('subject_id', 'ghost@example.com')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('ghost@example.com', $entry->metadata['email_attempted']);
    }

    #[Test]
    public function deactivated_login_records_deactivated_reason(): void
    {
        $admin = User::factory()->admin()->deactivated()->create();

        $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertStatus(403);

        $entry = Entry::query()->action('auth.login_failed')->forSubject($admin)->first();

        $this->assertNotNull($entry);
        $this->assertSame('deactivated', $entry->metadata['reason']);
    }

    #[Test]
    public function logout_records_an_audit_entry(): void
    {
        $admin = $this->adminWithPermissions([]);
        $token = $this->tokenFor($admin);

        $this->asToken($token)->postJson('/api/admin/logout')->assertOk();

        $entry = Entry::query()->action('auth.logout')->forActor($admin)->first();

        $this->assertNotNull($entry);
        $this->assertArrayHasKey('token_id', $entry->metadata);
    }

    #[Test]
    public function forgot_password_records_an_audit_entry_and_otp_issued(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'forgot@example.com']);

        $this->postJson('/api/admin/forgot-password', ['email' => $admin->email])->assertOk();

        $this->assertNotNull(
            Entry::query()->action('auth.password_forgot_requested')->forSubject($admin)->first(),
            'auth.password.forgot_requested entry missing'
        );

        $this->assertNotNull(
            Entry::query()->action('otp.issued')->forSubject($admin)->first(),
            'otp.issued entry missing'
        );
    }

    #[Test]
    public function forgot_password_for_unknown_email_does_not_record_any_entry(): void
    {
        $this->postJson('/api/admin/forgot-password', ['email' => 'ghost@example.com'])->assertOk();

        $this->assertSame(0, Entry::query()->action('auth.password_forgot_requested')->count());
        $this->assertSame(0, Entry::query()->action('otp.issued')->count());
    }

    #[Test]
    public function password_reset_records_otp_verified_and_reset_completed(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'reset@example.com']);

        // Issue an OTP directly so we have a known plaintext.
        $plain = '654321';
        Otp::query()->create([
            'email' => $admin->email,
            'otp' => Hash::make($plain),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->postJson('/api/admin/reset-password', [
            'email' => $admin->email,
            'otp' => $plain,
            'password' => 'NewStrongPass1!',
            'password_confirmation' => 'NewStrongPass1!',
        ])->assertOk();

        $this->assertNotNull(Entry::query()->action('otp.verified')->forSubject($admin)->first());
        $this->assertNotNull(Entry::query()->action('auth.password_reset_completed')->forSubject($admin)->first());
    }

    #[Test]
    public function wrong_otp_records_verify_failed_with_invalid_reason(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'badotp@example.com']);

        Otp::query()->create([
            'email' => $admin->email,
            'otp' => Hash::make('111111'),
            'expires_at' => now()->addMinutes(5),
        ]);

        $this->postJson('/api/admin/reset-password', [
            'email' => $admin->email,
            'otp' => '222222',
            'password' => 'NewStrongPass1!',
            'password_confirmation' => 'NewStrongPass1!',
        ])->assertStatus(422);

        $entry = Entry::query()->action('otp.verify_failed')->forSubject($admin)->first();
        $this->assertNotNull($entry);
        $this->assertSame('invalid', $entry->metadata['reason']);
    }

    #[Test]
    public function change_password_records_an_audit_entry(): void
    {
        $admin = $this->adminWithPermissions([]);
        $token = $this->tokenFor($admin);

        $this->asToken($token)->postJson('/api/admin/change-password', [
            'current_password' => 'password',
            'password' => 'BrandNewPass1!',
            'password_confirmation' => 'BrandNewPass1!',
        ])->assertOk();

        $this->assertNotNull(Entry::query()->action('auth.password_changed')->forSubject($admin)->first());
    }

    #[Test]
    public function profile_update_records_diff_for_changed_fields_only(): void
    {
        $admin = $this->adminWithPermissions([]);
        $admin->update(['name' => 'Old Name']);
        $token = $this->tokenFor($admin);

        $this->asToken($token)->putJson('/api/admin/profile', [
            'name' => 'New Name',
            'email' => $admin->email,
        ])->assertOk();

        $entry = Entry::query()->action('profile.updated')->forSubject($admin)->first();
        $this->assertNotNull($entry);
        $this->assertSame('Old Name', $entry->diff['name']['old']);
        $this->assertSame('New Name', $entry->diff['name']['new']);
        $this->assertArrayNotHasKey('email', $entry->diff);
    }

    #[Test]
    public function recorded_entries_never_contain_password_or_otp_values(): void
    {
        $admin = $this->adminWithPermissions([]);
        $token = $this->tokenFor($admin);

        $this->asToken($token)->postJson('/api/admin/change-password', [
            'current_password' => 'password',
            'password' => 'SuperSecretValue1!',
            'password_confirmation' => 'SuperSecretValue1!',
        ])->assertOk();

        $serialised = Entry::query()->get()->map(fn (Entry $e) => json_encode([
            'metadata' => $e->metadata,
            'diff' => $e->diff,
            'payload' => $e->payload,
        ]))->implode("\n");

        $this->assertStringNotContainsString('SuperSecretValue1!', $serialised);
        $this->assertStringNotContainsString('password', strtolower(implode(',', array_keys((array) Entry::query()->action('auth.password_changed')->first()?->diff ?? []))));
    }
}
