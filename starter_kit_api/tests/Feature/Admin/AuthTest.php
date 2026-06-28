<?php

namespace Tests\Feature\Admin;

use App\Enums\UserStatusEnum;
use App\Enums\UserTypeEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
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
                    'access_token',
                    'admin' => ['id', 'name', 'email', 'user_type', 'status'],
                ],
            ]);

        $this->assertSame($admin->id, $response->json('data.admin.id'));
        $this->assertSame(UserTypeEnum::Admin->value, $response->json('data.admin.user_type'));

        // Refresh token is delivered only as an httpOnly cookie, never in the body.
        $cookie = $response->getCookie('refresh_token', false);
        $this->assertNotNull($cookie, 'Login should set a refresh_token cookie.');
        $this->assertTrue($cookie->isHttpOnly(), 'Refresh cookie must be httpOnly.');
        $this->assertNotEmpty($cookie->getValue());
        $this->assertNull($response->json('data.refresh_token'), 'Refresh token must not be in the body.');
    }

    #[Test]
    public function login_issues_an_access_token_and_a_refresh_token_with_distinct_abilities(): void
    {
        $admin = User::factory()->admin()->create();

        $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertOk();

        $tokens = $admin->fresh()->tokens()->get();
        $this->assertCount(2, $tokens, 'Login should issue exactly two tokens.');
        $this->assertTrue($tokens->contains(fn ($t): bool => $t->abilities === ['access']));
        $this->assertTrue($tokens->contains(fn ($t): bool => $t->abilities === ['refresh']));
    }

    #[Test]
    public function refresh_rotates_tokens_and_returns_a_new_access_token(): void
    {
        $admin = User::factory()->admin()->create();

        $login = $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertOk();

        $refreshToken = $login->getCookie('refresh_token', false)->getValue();

        // withCredentials() mirrors the SPA's axios `withCredentials: true`, which
        // is what makes the browser attach the httpOnly refresh cookie.
        $response = $this->withCredentials()
            ->withUnencryptedCookie('refresh_token', $refreshToken)
            ->postJson('/api/admin/refresh');

        $response->assertOk()
            ->assertJsonPath('message', 'Token refreshed.')
            ->assertJsonStructure(['data' => ['access_token', 'admin' => ['id']]]);

        // The presented refresh token is rotated out (single-use).
        $this->assertNull(
            PersonalAccessToken::findToken($refreshToken),
            'Old refresh token should be revoked after rotation.'
        );

        // A fresh refresh cookie is issued.
        $newCookie = $response->getCookie('refresh_token', false);
        $this->assertNotNull($newCookie);
        $this->assertNotSame($refreshToken, $newCookie->getValue());
    }

    #[Test]
    public function the_new_access_token_from_refresh_can_access_protected_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $login = $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertOk();

        $refreshToken = $login->getCookie('refresh_token', false)->getValue();
        $newAccess = $this->withCredentials()
            ->withUnencryptedCookie('refresh_token', $refreshToken)
            ->postJson('/api/admin/refresh')->json('data.access_token');

        $this->withHeader('Authorization', "Bearer {$newAccess}")
            ->getJson('/api/admin/profile')
            ->assertOk();
    }

    #[Test]
    public function refresh_fails_when_the_cookie_is_missing_or_invalid(): void
    {
        $this->postJson('/api/admin/refresh')->assertStatus(401);

        $this->withCredentials()
            ->withUnencryptedCookie('refresh_token', 'not-a-real-token')
            ->postJson('/api/admin/refresh')
            ->assertStatus(401);
    }

    #[Test]
    public function a_used_refresh_token_cannot_be_replayed(): void
    {
        $admin = User::factory()->admin()->create();

        $login = $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertOk();

        $refreshToken = $login->getCookie('refresh_token', false)->getValue();

        $this->withCredentials()
            ->withUnencryptedCookie('refresh_token', $refreshToken)
            ->postJson('/api/admin/refresh')->assertOk();

        // Replaying the same refresh token after rotation must be rejected.
        $this->withCredentials()
            ->withUnencryptedCookie('refresh_token', $refreshToken)
            ->postJson('/api/admin/refresh')
            ->assertStatus(401);
    }

    #[Test]
    public function a_refresh_token_cannot_access_protected_routes(): void
    {
        $admin = User::factory()->admin()->create();
        $refreshToken = $admin->createToken('admin', ['refresh'])->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$refreshToken}")
            ->getJson('/api/admin/profile')
            ->assertStatus(403);
    }

    #[Test]
    public function mobile_login_returns_the_refresh_token_in_the_body_and_no_cookie(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->withHeader('X-Client', 'mobile')
            ->postJson('/api/admin/login', [
                'email' => $admin->email,
                'password' => 'password',
            ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'admin' => ['id']]]);

        $this->assertNotEmpty($response->json('data.refresh_token'));
        $this->assertNull(
            $response->getCookie('refresh_token', false),
            'Mobile clients must not receive a refresh cookie.'
        );
    }

    #[Test]
    public function mobile_refresh_via_header_rotates_and_returns_a_new_refresh_token_in_the_body(): void
    {
        $admin = User::factory()->admin()->create();

        $refreshToken = $this->withHeader('X-Client', 'mobile')
            ->postJson('/api/admin/login', [
                'email' => $admin->email,
                'password' => 'password',
            ])->json('data.refresh_token');

        $response = $this->withHeader('X-Refresh-Token', $refreshToken)
            ->postJson('/api/admin/refresh');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'admin' => ['id']]]);

        $newRefresh = $response->json('data.refresh_token');
        $this->assertNotEmpty($newRefresh);
        $this->assertNotSame($refreshToken, $newRefresh);
        $this->assertNull($response->getCookie('refresh_token', false), 'No cookie for mobile refresh.');

        // Rotation: the presented token is revoked, the new access token works.
        $this->assertNull(PersonalAccessToken::findToken($refreshToken));
        $this->withHeader('Authorization', "Bearer {$response->json('data.access_token')}")
            ->getJson('/api/admin/profile')
            ->assertOk();
    }

    #[Test]
    public function mobile_refresh_with_an_invalid_header_token_fails(): void
    {
        $this->withHeader('X-Refresh-Token', 'not-a-real-token')
            ->postJson('/api/admin/refresh')
            ->assertStatus(401);
    }

    #[Test]
    public function a_used_mobile_refresh_token_cannot_be_replayed(): void
    {
        $admin = User::factory()->admin()->create();

        $refreshToken = $this->withHeader('X-Client', 'mobile')
            ->postJson('/api/admin/login', [
                'email' => $admin->email,
                'password' => 'password',
            ])->json('data.refresh_token');

        $this->withHeader('X-Refresh-Token', $refreshToken)
            ->postJson('/api/admin/refresh')->assertOk();

        $this->withHeader('X-Refresh-Token', $refreshToken)
            ->postJson('/api/admin/refresh')
            ->assertStatus(401);
    }

    #[Test]
    public function mobile_logout_revokes_both_tokens(): void
    {
        $admin = User::factory()->admin()->create();

        $login = $this->withHeader('X-Client', 'mobile')
            ->postJson('/api/admin/login', [
                'email' => $admin->email,
                'password' => 'password',
            ]);

        $accessToken = $login->json('data.access_token');
        $refreshToken = $login->json('data.refresh_token');

        $this->withHeader('Authorization', "Bearer {$accessToken}")
            ->withHeader('X-Refresh-Token', $refreshToken)
            ->postJson('/api/admin/logout')
            ->assertOk();

        $this->assertSame(0, $admin->fresh()->tokens()->count(), 'Both tokens should be revoked.');
    }

    #[Test]
    public function web_login_keeps_the_refresh_token_out_of_the_body(): void
    {
        $admin = User::factory()->admin()->create();

        // No X-Client header => browser mode => cookie only, never in the body.
        $response = $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertOk();
        $this->assertNull($response->json('data.refresh_token'));
        $this->assertNotNull($response->getCookie('refresh_token', false));
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
    public function logout_revokes_the_paired_refresh_token_and_clears_the_cookie(): void
    {
        $admin = User::factory()->admin()->create();

        $login = $this->postJson('/api/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertOk();

        $accessToken = $login->json('data.access_token');
        $refreshToken = $login->getCookie('refresh_token', false)->getValue();

        $response = $this->withCredentials()
            ->withHeader('Authorization', "Bearer {$accessToken}")
            ->withUnencryptedCookie('refresh_token', $refreshToken)
            ->postJson('/api/admin/logout');

        $response->assertOk()->assertJsonPath('message', 'Logged out.');

        $this->assertSame(0, $admin->fresh()->tokens()->count(), 'Both tokens should be revoked.');

        // The Set-Cookie clears the refresh cookie (expired / empty value).
        $cleared = $response->getCookie('refresh_token', false);
        $this->assertNotNull($cleared);
        $this->assertEmpty($cleared->getValue());
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
