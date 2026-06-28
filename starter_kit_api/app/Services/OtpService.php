<?php

namespace App\Services;

use App\Exceptions\OtpAlreadyIssuedException;
use App\Models\Otp;
use App\Models\User;
use Chronicle\Facades\Chronicle;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class OtpService
{
    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_PHONE = 'phone';

    public const DEFAULT_TTL_MINUTES = 5;

    public const DEFAULT_LENGTH = 6;

    public const MAX_ATTEMPTS = 5;

    /**
     * Verify lock TTL — long enough to cover a single verify call (DB SELECT
     * + bcrypt check + atomic UPDATE) with margin if the request crashes.
     */
    public const LOCK_TTL_SECONDS = 5;

    /**
     * How long a contending verify will wait for the lock before bailing out
     * with a `false` (treated by the controller as "Invalid or expired OTP.").
     */
    public const LOCK_BLOCK_SECONDS = 2;

    /**
     * Issue a new OTP for the given channel + identifier.
     * Returns the plaintext OTP and its expiry — the caller is responsible for delivering the OTP.
     *
     * Pass $auditSubject to record an `otp.issued` Chronicle entry with that
     * subject (typically the User the OTP is being sent to). The OTP value
     * itself is never logged.
     *
     * @return array{otp: string, expires_at: Carbon}
     *
     * @throws OtpAlreadyIssuedException when a non-expired OTP still exists.
     */
    public function issue(string $channel, string $identifier, ?User $auditSubject = null): array
    {
        $column = $this->columnFor($channel);

        Otp::query()
            ->where($column, $identifier)
            ->where(function ($query): void {
                $query->where('expires_at', '<=', Carbon::now())
                    ->orWhereNotNull('consumed_at');
            })
            ->delete();

        $existing = Otp::query()
            ->where($column, $identifier)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', Carbon::now())
            ->exists();

        if ($existing) {
            throw new OtpAlreadyIssuedException(
                'An OTP was already sent. Please wait until it expires before requesting a new one.'
            );
        }

        $plain = $this->generatePlain();
        $expiresAt = Carbon::now()->addMinutes(self::DEFAULT_TTL_MINUTES);

        Otp::query()->create([
            $column => $identifier,
            'otp' => Hash::make($plain),
            'expires_at' => $expiresAt,
        ]);

        if ($auditSubject !== null) {
            Chronicle::record()
                ->actor('system')
                ->action('otp.issued')
                ->subject($auditSubject)
                ->metadata([
                    'channel' => $channel,
                    'expires_at' => $expiresAt->toIso8601String(),
                ])
                ->tags(['auth'])
                ->commit();
        }

        return [
            'otp' => $plain,
            'expires_at' => $expiresAt,
        ];
    }

    public function verify(string $channel, string $identifier, string $otp, ?User $auditSubject = null): bool
    {
        $column = $this->columnFor($channel);
        $lockKey = "otp:verify:{$channel}:{$identifier}";

        // Serialize concurrent verifies for the same identifier so the
        // attempts-counter throttle (MAX_ATTEMPTS) cannot be raced past.
        // A contending caller that can't acquire the lock within
        // LOCK_BLOCK_SECONDS gets a `false`, which the controller surfaces
        // as the same generic "Invalid or expired OTP." response.
        try {
            [$ok, $reason] = Cache::lock($lockKey, self::LOCK_TTL_SECONDS)
                ->block(self::LOCK_BLOCK_SECONDS, function () use ($column, $identifier, $otp): array {
                    $row = Otp::query()
                        ->where($column, $identifier)
                        ->whereNull('consumed_at')
                        ->where('expires_at', '>', Carbon::now())
                        ->where('attempts', '<', self::MAX_ATTEMPTS)
                        ->orderByDesc('id')
                        ->first();

                    if (! $row) {
                        // Either no OTP at all, or it expired / was consumed / hit the max.
                        // Distinguish "exhausted" so the auditor sees brute-force patterns.
                        $exhausted = Otp::query()
                            ->where($column, $identifier)
                            ->where('attempts', '>=', self::MAX_ATTEMPTS)
                            ->exists();

                        return [false, $exhausted ? 'exhausted' : 'invalid_or_expired'];
                    }

                    if (! Hash::check($otp, $row->otp)) {
                        $row->increment('attempts');

                        return [false, 'invalid'];
                    }

                    // Atomic consume: only one concurrent verifier wins. The conditional update
                    // returns 1 only if consumed_at was still NULL at the moment of the write.
                    $consumed = Otp::query()
                        ->whereKey($row->getKey())
                        ->whereNull('consumed_at')
                        ->update(['consumed_at' => Carbon::now()]);

                    return [$consumed === 1, $consumed === 1 ? 'verified' : 'race_lost'];
                });
        } catch (LockTimeoutException) {
            $ok = false;
            $reason = 'lock_timeout';
        }

        if ($auditSubject !== null) {
            Chronicle::record()
                ->actor('system')
                ->action($ok ? 'otp.verified' : 'otp.verify_failed')
                ->subject($auditSubject)
                ->metadata([
                    'channel' => $channel,
                    'reason' => $reason,
                ])
                ->tags($ok ? ['auth'] : ['auth', 'security'])
                ->commit();
        }

        return $ok;
    }

    public function purge(string $channel, string $identifier): void
    {
        $column = $this->columnFor($channel);

        Otp::query()->where($column, $identifier)->delete();
    }

    protected function columnFor(string $channel): string
    {
        return match ($channel) {
            self::CHANNEL_EMAIL => 'email',
            self::CHANNEL_PHONE => 'phone',
            default => throw new InvalidArgumentException("Unsupported OTP channel: {$channel}"),
        };
    }

    protected function generatePlain(): string
    {
        $min = (int) str_pad('1', self::DEFAULT_LENGTH, '0');
        $max = (int) str_pad('9', self::DEFAULT_LENGTH, '9');

        return (string) random_int($min, $max);
    }
}
