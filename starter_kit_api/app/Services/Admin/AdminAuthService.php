<?php

namespace App\Services\Admin;

use App\Enums\UserStatusEnum;
use App\Exceptions\AccountDeactivatedException;
use App\Exceptions\AdminNotFoundException;
use App\Exceptions\InvalidCredentialsException;
use App\Models\User;
use App\Support\Audit\AnonymousLoginAttempt;
use Chronicle\Facades\Chronicle;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AdminAuthService
{
    /**
     * Ability granted to short-lived access tokens. Required by the protected
     * admin routes; refresh tokens deliberately lack it so they cannot be used
     * to call business endpoints.
     */
    public const ACCESS_ABILITY = 'access';

    /**
     * Ability granted to longer-lived refresh tokens. Only the refresh endpoint
     * accepts it; it exchanges the refresh token for a fresh token pair.
     */
    public const REFRESH_ABILITY = 'refresh';

    /**
     * Process-lifetime cache of a bcrypt hash used to equalize login timing when
     * the email doesn't resolve to a user. Computed once via Hash::make so its
     * work factor matches whatever the app's bcrypt rounds config is.
     */
    private static ?string $dummyHash = null;

    /**
     * @return array{access_token: string, refresh_token: string, user: User}
     *
     * @throws InvalidCredentialsException
     * @throws AccountDeactivatedException
     */
    public function login(string $email, string $password, ?string $deviceName = null): array
    {
        $user = User::query()->admins()->where('email', $email)->first();

        // Always run a bcrypt comparison so "user not found" and "wrong password"
        // take roughly the same wall-clock time. Prevents timing-based email enumeration.
        $passwordOk = Hash::check($password, $user?->password ?? $this->dummyPasswordHash());

        if (! $user || ! $passwordOk) {
            $this->recordLoginFailed($email, $user, 'invalid_credentials');

            throw new InvalidCredentialsException('Invalid credentials.');
        }

        if (! $user->isActive()) {
            $this->recordLoginFailed($email, $user, 'deactivated');

            throw new AccountDeactivatedException(
                'Account is deactivated, please contact administrator.'
            );
        }

        $tokens = $this->issueTokenPair($user, $deviceName);

        $user->load(['roles.permissions', 'permissions']);

        Chronicle::record()
            ->actor($user)
            ->action('auth.login_succeeded')
            ->subject($user)
            ->metadata([
                'device_name' => $deviceName,
            ])
            ->tags(['auth'])
            ->commit();

        return [...$tokens, 'user' => $user];
    }

    /**
     * Exchange a valid refresh token for a brand-new access/refresh token pair.
     * The presented refresh token is revoked (rotation) and its expiry slides
     * forward, so an actively used session never has to fully re-authenticate.
     *
     * @return array{access_token: string, refresh_token: string, user: User}
     *
     * @throws InvalidCredentialsException
     * @throws AccountDeactivatedException
     */
    public function refresh(?string $refreshPlainText): array
    {
        $token = $refreshPlainText ? PersonalAccessToken::findToken($refreshPlainText) : null;

        if (! $token
            || ! $token->can(self::REFRESH_ABILITY)
            || ($token->expires_at && $token->expires_at->isPast())) {
            throw new InvalidCredentialsException('Invalid or expired refresh token.');
        }

        /** @var User $user */
        $user = $token->tokenable;

        if (! $user->isActive()) {
            throw new AccountDeactivatedException(
                'Account is deactivated, please contact administrator.'
            );
        }

        // Rotate: the presented refresh token can only ever be used once.
        $deviceName = $token->name;
        $token->delete();

        $tokens = $this->issueTokenPair($user, $deviceName);

        $user->load(['roles.permissions', 'permissions']);

        Chronicle::record()
            ->actor($user)
            ->action('auth.token_refreshed')
            ->subject($user)
            ->metadata(['device_name' => $deviceName])
            ->tags(['auth'])
            ->commit();

        return [...$tokens, 'user' => $user];
    }

    /**
     * Revoke the current access token and, when provided, the paired refresh
     * token from the request cookie — logging out just this session.
     */
    public function logout(User $user, PersonalAccessToken $token, ?string $refreshPlainText = null): void
    {
        $tokenId = $token->getKey();
        $token->delete();

        $refreshToken = $refreshPlainText ? PersonalAccessToken::findToken($refreshPlainText) : null;
        if ($refreshToken && $refreshToken->tokenable_id === $user->getKey()) {
            $refreshToken->delete();
        }

        Chronicle::record()
            ->actor($user)
            ->action('auth.logout')
            ->subject($user)
            ->metadata(['token_id' => $tokenId])
            ->tags(['auth'])
            ->commit();
    }

    /**
     * Issue a short-lived access token and a longer-lived refresh token, each
     * with its own ability and expiry drawn from the sanctum config.
     *
     * @return array{access_token: string, refresh_token: string}
     */
    private function issueTokenPair(User $user, ?string $deviceName): array
    {
        $name = $deviceName ?: 'admin';

        $accessToken = $user->createToken(
            $name,
            [self::ACCESS_ABILITY],
            now()->addMinutes((int) config('sanctum.access_token_expiration')),
        )->plainTextToken;

        $refreshToken = $user->createToken(
            $name,
            [self::REFRESH_ABILITY],
            now()->addMinutes((int) config('sanctum.refresh_token_expiration')),
        )->plainTextToken;

        return ['access_token' => $accessToken, 'refresh_token' => $refreshToken];
    }

    /**
     * Look up an admin account for password-reset flows (forgot / reset).
     *
     * @throws AdminNotFoundException
     * @throws AccountDeactivatedException
     */
    public function findAdminForPasswordFlow(string $email): User
    {
        $user = User::query()->admins()->where('email', $email)->first();

        if (! $user) {
            throw new AdminNotFoundException('No admin account found for this email.');
        }

        if (! $user->isActive()) {
            throw new AccountDeactivatedException(
                'Account is deactivated, please contact administrator.'
            );
        }

        return $user;
    }

    public function recordForgotPasswordRequested(User $user): void
    {
        Chronicle::record()
            ->actor('system')
            ->action('auth.password_forgot_requested')
            ->subject($user)
            ->metadata(['channel' => 'email'])
            ->tags(['auth'])
            ->commit();
    }

    public function applyNewPassword(User $user, string $password): void
    {
        $user->forceFill(['password' => Hash::make($password)])->save();
        $tokensRevoked = $user->tokens()->count();
        $user->tokens()->delete();

        Chronicle::record()
            ->actor($user)
            ->action('auth.password_reset_completed')
            ->subject($user)
            ->metadata([
                'tokens_revoked' => $tokensRevoked,
            ])
            ->tags(['auth', 'security'])
            ->commit();
    }

    public function changePassword(User $user, string $newPassword, int $currentTokenId): void
    {
        $user->forceFill(['password' => Hash::make($newPassword)])->save();
        $otherTokensRevoked = $user->tokens()->where('id', '!=', $currentTokenId)->count();
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        Chronicle::record()
            ->actor($user)
            ->action('auth.password_changed')
            ->subject($user)
            ->metadata([
                'other_tokens_revoked' => $otherTokensRevoked,
            ])
            ->tags(['auth', 'security'])
            ->commit();
    }

    public function updateProfile(User $user, string $name, string $email): User
    {
        $changes = [];
        if ($user->name !== $name) {
            $changes['name'] = ['old' => $user->name, 'new' => $name];
        }
        if ($user->email !== $email) {
            $changes['email'] = ['old' => $user->email, 'new' => $email];
        }

        $user->forceFill(['name' => $name, 'email' => $email])->save();

        if (! empty($changes)) {
            Chronicle::record()
                ->actor($user)
                ->action('profile.updated')
                ->subject($user)
                ->diff($changes)
                ->tags(['profile'])
                ->commit();
        }

        return $user->refresh();
    }

    /**
     * Ensure the deactivated check is applied consistently elsewhere.
     */
    public function isActive(User $user): bool
    {
        return $user->status === UserStatusEnum::Active;
    }

    /**
     * Record a failed login attempt. Used for both invalid-credentials and
     * deactivated paths so the security team can filter by reason.
     */
    protected function recordLoginFailed(string $email, ?User $user, string $reason): void
    {
        Chronicle::record()
            ->actor('system')
            ->action('auth.login_failed')
            ->subject($user ?? new AnonymousLoginAttempt($email))
            ->metadata([
                'email_attempted' => $email,
                'reason' => $reason,
            ])
            ->tags(['auth', 'security'])
            ->commit();
    }

    private function dummyPasswordHash(): string
    {
        return self::$dummyHash ??= Hash::make('login-timing-equalizer-dummy');
    }
}
