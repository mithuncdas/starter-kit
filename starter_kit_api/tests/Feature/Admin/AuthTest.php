<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatusEnum;
use App\Enums\UserTypeEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function admin_can_login_with_valid_credentials(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $response = $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success', 'message',
                'data' => [
                    'token',
                    'admin' => ['id', 'name', 'email', 'user_type', 'status'],
                ],
            ]);

        $this->assertSame($admin->id, $response->json('data.admin.id'));
        $this->assertSame(UserTypeEnum::Admin->value, $response->json('data.admin.user_type'));
    }

    #[Test]
    public function login_fails_with_wrong_password(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    #[Test]
    public function login_fails_for_non_admin_user(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Invalid credentials.');
    }

    #[Test]
    public function login_returns_403_for_deactivated_admin(): void
    {
        $admin = User::factory()->admin()->deactivated()->create();

        $response = $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Account is deactivated, please contact administrator.');

        $this->assertSame(0, $admin->tokens()->count(), 'No token should have been issued.');
    }

    #[Test]
    public function login_is_rate_limited_after_five_attempts(): void
    {
        $admin = User::factory()->admin()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/admin/login', [
                'email' => $admin->email,
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    #[Test]
    public function logout_revokes_the_current_token_only(): void
    {
        $admin = User::factory()->admin()->create();
        $current = $admin->createToken('current')->plainTextToken;
        $admin->createToken('other');

        $response = $this->withHeader('Authorization', "Bearer {$current}")
            ->postJson('/api/admin/logout');

        $response->assertOk()->assertJsonPath('message', 'Logged out.');
        $this->assertSame(1, $admin->fresh()->tokens()->count(), 'Only the "other" token should remain.');
    }

    #[Test]
    public function protected_routes_reject_requests_without_a_token(): void
    {
        $this->getJson('/api/admin/profile')->assertStatus(401);
        $this->postJson('/api/admin/logout')->assertStatus(401);
        $this->postJson('/api/admin/change-password')->assertStatus(401);
    }

    #[Test]
    public function regular_user_token_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create([
            'user_type' => UserTypeEnum::User,
            'status' => UserStatusEnum::Active,
        ]);
        $token = $user->createToken('user')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/profile')
            ->assertStatus(403);
    }

    #[Test]
    public function deactivated_admin_with_token_cannot_access_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('device')->plainTextToken;

        $admin->forceFill(['status' => UserStatusEnum::Inactive])->save();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/admin/profile')
            ->assertStatus(403)
            ->assertJsonPath('message', 'Account is deactivated, please contact administrator.');
    }
}
