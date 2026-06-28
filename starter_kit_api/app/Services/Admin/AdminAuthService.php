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
     * Process-lifetime cache of a bcrypt hash used to equalize login timing when
     * the email doesn't resolve to a user. Computed once via Hash::make so its
     * work factor matches whatever the app's bcrypt rounds config is.
     */
    private static ?string $dummyHash = null;

    /**
     * @return array{token: string, user: User}
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

        $token = $user->createToken($deviceName ?: 'admin')->plainTextToken;

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

        return ['token' => $token, 'user' => $user];
    }

    public function logout(User $user, PersonalAccessToken $token): void
    {
        $tokenId = $token->getKey();
        $token->delete();

        Chronicle::record()
            ->actor($user)
            ->action('auth.logout')
            ->subject($user)
            ->metadata(['token_id' => $tokenId])
            ->tags(['auth'])
            ->commit();
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
