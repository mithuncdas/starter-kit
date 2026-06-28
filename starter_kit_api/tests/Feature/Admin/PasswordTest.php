<?php

namespace Tests\Feature\Admin;

use App\Models\Otp;
use App\Models\User;
use App\Notifications\Admin\AdminPasswordResetOtpNotification;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use LazilyRefreshDatabase;

    /* ----------------------------------------------------------------------
     | Forgot password
     |---------------------------------------------------------------------- */

    private const GENERIC_FORGOT_MESSAGE = 'If an admin account exists for that email, an OTP has been sent.';

    #[Test]
    public function forgot_password_issues_otp_for_active_admin_and_sends_notification(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $response = $this->postJson('/api/admin/forgot-password', [
            'email' => $admin->email,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', self::GENERIC_FORGOT_MESSAGE);

        $this->assertDatabaseCount('otps', 1);
        $otpRow = Otp::query()->where('email', $admin->email)->first();
        $this->assertNotNull($otpRow);
        $this->assertNull($otpRow->phone);
        $this->assertTrue($otpRow->expires_at->isFuture());
        // OTP must be hashed, not stored plaintext
        $this->assertNotSame(6, mb_strlen($otpRow->otp), 'Hashed OTP should not be 6 chars long.');

        Notification::assertSentOnDemand(AdminPasswordResetOtpNotification::class);
    }

    #[Test]
    public function forgot_password_returns_generic_success_for_unknown_email_without_issuing_otp(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/admin/forgot-password', [
            'email' => 'unknown@example.com',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', self::GENERIC_FORGOT_MESSAGE);

        $this->assertDatabaseCount('otps', 0);
        Notification::assertNothingSent();
    }

    #[Test]
    public function forgot_password_returns_generic_success_for_non_admin_email_without_issuing_otp(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $response = $this->postJson('/api/admin/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', self::GENERIC_FORGOT_MESSAGE);

        $this->assertDatabaseCount('otps', 0);
        Notification::assertNothingSent();
    }

    #[Test]
    public function forgot_password_returns_generic_success_for_deactivated_admin_without_issuing_otp(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->deactivated()->create();

        $response = $this->postJson('/api/admin/forgot-password', [
            'email' => $admin->email,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', self::GENERIC_FORGOT_MESSAGE);

        $this->assertDatabaseCount('otps', 0);
        Notification::assertNothingSent();
    }

    #[Test]
    public function forgot_password_silently_suppresses_duplicate_requests_while_otp_still_valid(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $this->postJson('/api/admin/forgot-password', ['email' => $admin->email])
            ->assertOk()
            ->assertJsonPath('message', self::GENERIC_FORGOT_MESSAGE);

        // Second request returns the same generic success (no enumeration leak),
        // but does not create a second OTP or re-send the notification.
        $this->postJson('/api/admin/forgot-password', ['email' => $admin->email])
            ->assertOk()
            ->assertJsonPath('message', self::GENERIC_FORGOT_MESSAGE);

        $this->assertDatabaseCount('otps', 1);
        Notification::assertSentOnDemandTimes(AdminPasswordResetOtpNotification::class, 1);
    }

    #[Test]
    public function forgot_password_is_allowed_again_once_previous_otp_has_expired(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        $this->postJson('/api/admin/forgot-password', ['email' => $admin->email])->assertOk();
        $firstOtp = Otp::query()->where('email', $admin->email)->first();

        // Manually expire the first OTP
        $firstOtp->forceFill(['expires_at' => Carbon::now()->subMinute()])->save();

        // A second forgot-password should: purge expired, create a new row, send a new notification
        Carbon::setTestNow(Carbon::now()->addSeconds(30)); // bypass the throttle key reuse window safely

        $this->postJson('/api/admin/forgot-password', ['email' => $admin->email])->assertOk();

        $this->assertSame(1, Otp::query()->count(), 'Old expired OTP should have been purged.');
        $current = Otp::query()->where('email', $admin->email)->first();
        $this->assertTrue($current->expires_at->isFuture());

        Carbon::setTestNow();
    }

    #[Test]
    public function forgot_password_is_rate_limited_after_three_attempts(): void
    {
        Notification::fake();

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/admin/forgot-password', [
                'email' => 'someone@example.com',
            ])->assertOk();
        }

        $this->postJson('/api/admin/forgot-password', [
            'email' => 'someone@example.com',
        ])->assertStatus(429);
    }

    /* ----------------------------------------------------------------------
     | Reset password
     |---------------------------------------------------------------------- */

    #[Test]
    public function reset_password_succeeds_with_valid_otp_and_revokes_all_tokens(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->createToken('device-a');
        $admin->createToken('device-b');

        // Trigger forgot to issue OTP
        Notification::fake();
        $this->postJson('/api/admin/forgot-password', ['email' => $admin->email])->assertOk();
        $plain = $this->grabOtpFromNotification($admin->email);

        $response = $this->postJson('/api/admin/reset-password', [
            'email' => $admin->email,
            'otp' => $plain,
            'password' => 'NewStrongPass!123',
            'password_confirmation' => 'NewStrongPass!123',
        ]);

        $response->assertOk()->assertJsonPath('message', 'Password reset successful. Please log in again.');

        $admin->refresh();
        $this->assertTrue(Hash::check('NewStrongPass!123', $admin->password));
        $this->assertSame(0, $admin->tokens()->count(), 'All tokens must be revoked.');
        $this->assertSame(0, Otp::query()->count(), 'All OTPs for this email must be purged.');
    }

    #[Test]
    public function reset_password_fails_with_wrong_otp(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $this->postJson('/api/admin/forgot-password', ['email' => $admin->email])->assertOk();

        $response = $this->postJson('/api/admin/reset-password', [
            'email' => $admin->email,
            'otp' => '000000',
            'password' => 'NewStrongPass!123',
            'password_confirmation' => 'NewStrongPass!123',
        ]);

        $response->assertStatus(422)->assertJsonPath('message', 'Invalid or expired OTP.');
        $this->assertDatabaseCount('otps', 1);
    }

    #[Test]
    public function reset_password_fails_with_expired_otp(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $this->postJson('/api/admin/forgot-password', ['email' => $admin->email])->assertOk();
        $plain = $this->grabOtpFromNotification($admin->email);

        Otp::query()->where('email', $admin->email)
            ->update(['expires_at' => Carbon::now()->subMinute()]);

        $response = $this->postJson('/api/admin/reset-password', [
            'email' => $admin->email,
            'otp' => $plain,
            'password' => 'NewStrongPass!123',
            'password_confirmation' => 'NewStrongPass!123',
        ]);

        $response->assertStatus(422)->assertJsonPath('message', 'Invalid or expired OTP.');
    }

    #[Test]
    public function reset_password_fails_when_no_otp_exists(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->postJson('/api/admin/reset-password', [
            'email' => $admin->email,
            'otp' => '123456',
            'password' => 'NewStrongPass!123',
            'password_confirmation' => 'NewStrongPass!123',
        ]);

        $response->assertStatus(422)->assertJsonPath('message', 'Invalid or expired OTP.');
    }

    #[Test]
    public function reset_password_returns_generic_invalid_otp_for_unknown_admin(): void
    {
        $response = $this->postJson('/api/admin/reset-password', [
            'email' => 'unknown@example.com',
            'otp' => '123456',
            'password' => 'NewStrongPass!123',
            'password_confirmation' => 'NewStrongPass!123',
        ]);

        $response->assertStatus(422)->assertJsonPath('message', 'Invalid or expired OTP.');
    }

    #[Test]
    public function reset_password_returns_generic_invalid_otp_for_deactivated_admin(): void
    {
        $admin = User::factory()->admin()->deactivated()->create();

        $response = $this->postJson('/api/admin/reset-password', [
            'email' => $admin->email,
            'otp' => '123456',
            'password' => 'NewStrongPass!123',
            'password_confirmation' => 'NewStrongPass!123',
        ]);

        $response->assertStatus(422)->assertJsonPath('message', 'Invalid or expired OTP.');
    }

    #[Test]
    public function otp_verify_locks_out_after_max_attempts_even_with_correct_code(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        /** @var OtpService $service */
        $service = app(OtpService::class);
        $issued = $service->issue(OtpService::CHANNEL_EMAIL, $admin->email);

        // Five wrong attempts bring the row to OtpService::MAX_ATTEMPTS = 5.
        for ($i = 1; $i <= OtpService::MAX_ATTEMPTS; $i++) {
            $this->assertFalse($service->verify(OtpService::CHANNEL_EMAIL, $admin->email, '000000'));
        }

        $this->assertSame(
            OtpService::MAX_ATTEMPTS,
            Otp::query()->where('email', $admin->email)->value('attempts'),
        );

        // Even with the CORRECT OTP, verify must now return false: the row is no longer
        // selectable because attempts >= MAX_ATTEMPTS.
        $this->assertFalse($service->verify(OtpService::CHANNEL_EMAIL, $admin->email, $issued['otp']));
    }

    #[Test]
    public function otp_verify_returns_false_and_preserves_otp_when_lock_is_contended(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();

        /** @var OtpService $service */
        $service = app(OtpService::class);
        $issued = $service->issue(OtpService::CHANNEL_EMAIL, $admin->email);

        // Externally hold the per-identifier verify lock to simulate a concurrent
        // verify in flight. The contending call must wait LOCK_BLOCK_SECONDS,
        // then return false WITHOUT consuming the OTP or incrementing attempts.
        $lock = Cache::lock(
            'otp:verify:'.OtpService::CHANNEL_EMAIL.":{$admin->email}",
            OtpService::LOCK_TTL_SECONDS,
        );
        $this->assertTrue($lock->get(), 'Test setup: lock should be acquirable.');

        try {
            $start = microtime(true);
            $result = $service->verify(OtpService::CHANNEL_EMAIL, $admin->email, $issued['otp']);
            $elapsed = microtime(true) - $start;

            $this->assertFalse($result, 'verify must return false when it cannot acquire the lock.');
            // Laravel's block() polls in integer-second buckets, so the wall-clock wait can land
            // slightly under LOCK_BLOCK_SECONDS depending on sub-second alignment. The point of the
            // assertion is to prove the block was honored (a clean uncontended verify is ~50ms).
            $this->assertGreaterThanOrEqual(
                1.0,
                $elapsed,
                'verify should have waited for the lock before bailing — not returned instantly.',
            );

            $row = Otp::query()->where('email', $admin->email)->first();
            $this->assertNotNull($row, 'OTP row must still exist — contended verify must not touch it.');
            $this->assertNull($row->consumed_at, 'OTP must not be consumed by a contended verify.');
            $this->assertSame(0, $row->attempts, 'Attempts must not increment when verify is locked out.');
        } finally {
            $lock->release();
        }
    }

    #[Test]
    public function reset_password_rejects_weak_password(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $this->postJson('/api/admin/forgot-password', ['email' => $admin->email])->assertOk();
        $plain = $this->grabOtpFromNotification($admin->email);

        // Too short, no symbols, no mixed case, no numbers — should fail the strong-password rule.
        $response = $this->postJson('/api/admin/reset-password', [
            'email' => $admin->email,
            'otp' => $plain,
            'password' => 'weakpass',
            'password_confirmation' => 'weakpass',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);

        // Validation runs before OTP verify, so the OTP must remain unconsumed.
        $this->assertSame(1, Otp::query()->whereNull('consumed_at')->count());
    }

    #[Test]
    public function change_password_rejects_weak_password(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('current')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/change-password', [
                'current_password' => 'password',
                'password' => 'weakpass',
                'password_confirmation' => 'weakpass',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    #[Test]
    public function reset_password_rejects_reused_otp(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $this->postJson('/api/admin/forgot-password', ['email' => $admin->email])->assertOk();
        $plain = $this->grabOtpFromNotification($admin->email);

        // First successful reset purges the OTP (controller calls otpService->purge).
        $this->postJson('/api/admin/reset-password', [
            'email' => $admin->email,
            'otp' => $plain,
            'password' => 'NewStrongPass!123',
            'password_confirmation' => 'NewStrongPass!123',
        ])->assertOk();

        // Same OTP cannot be re-used: same generic invalid response, no password change.
        $this->postJson('/api/admin/reset-password', [
            'email' => $admin->email,
            'otp' => $plain,
            'password' => 'AnotherPass!456',
            'password_confirmation' => 'AnotherPass!456',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Invalid or expired OTP.');

        $admin->refresh();
        $this->assertTrue(Hash::check('NewStrongPass!123', $admin->password));
        $this->assertFalse(Hash::check('AnotherPass!456', $admin->password));
    }

    #[Test]
    public function reset_password_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/admin/reset-password', [
                'email' => 'spammer@example.com',
                'otp' => '111111',
                'password' => 'NewStrongPass!123',
                'password_confirmation' => 'NewStrongPass!123',
            ])->assertStatus(422);
        }

        $this->postJson('/api/admin/reset-password', [
            'email' => 'spammer@example.com',
            'otp' => '111111',
            'password' => 'NewStrongPass!123',
            'password_confirmation' => 'NewStrongPass!123',
        ])->assertStatus(429);
    }

    /* ----------------------------------------------------------------------
     | Change password
     |---------------------------------------------------------------------- */

    #[Test]
    public function change_password_succeeds_and_keeps_current_token_but_revokes_others(): void
    {
        $admin = User::factory()->admin()->create();
        $current = $admin->createToken('current')->plainTextToken;
        $admin->createToken('other-1');
        $admin->createToken('other-2');

        $response = $this->withHeader('Authorization', "Bearer {$current}")
            ->postJson('/api/admin/change-password', [
                'current_password' => 'password',
                'password' => 'NewStrongPass!123',
                'password_confirmation' => 'NewStrongPass!123',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password changed. Other sessions have been signed out.');

        $admin->refresh();
        $this->assertTrue(Hash::check('NewStrongPass!123', $admin->password));
        $this->assertSame(1, $admin->tokens()->count(), 'Only the current token should remain.');

        // Current token should still authenticate the next request
        $this->withHeader('Authorization', "Bearer {$current}")
            ->getJson('/api/admin/profile')
            ->assertOk();
    }

    #[Test]
    public function change_password_rejects_wrong_current_password(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('current')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/change-password', [
                'current_password' => 'not-the-right-password',
                'password' => 'NewStrongPass!123',
                'password_confirmation' => 'NewStrongPass!123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    #[Test]
    public function change_password_requires_new_password_to_differ(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('current')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/admin/change-password', [
                'current_password' => 'password',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /* ----------------------------------------------------------------------
     | Helper
     |---------------------------------------------------------------------- */

    protected function grabOtpFromNotification(string $email): string
    {
        $plain = null;

        Notification::assertSentOnDemand(
            AdminPasswordResetOtpNotification::class,
            function (AdminPasswordResetOtpNotification $notification) use (&$plain): bool {
                $plain = $notification->otp;

                return true;
            }
        );

        $this->assertNotNull($plain, "No OTP notification captured for {$email}");

        return $plain;
    }
}
