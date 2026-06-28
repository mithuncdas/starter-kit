<?php

namespace App\Notifications\Admin;

use App\Services\OtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminPasswordResetOtpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $otp) {}

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        $ttl = OtpService::DEFAULT_TTL_MINUTES;

        return (new MailMessage)
            ->subject('Admin password reset OTP')
            ->greeting('Hello,')
            ->line('Use the following one-time password to reset your admin account password:')
            ->line("**{$this->otp}**")
            ->line("This OTP will expire in {$ttl} minutes.")
            ->line('If you did not request a password reset, you can safely ignore this email.');
    }
}
