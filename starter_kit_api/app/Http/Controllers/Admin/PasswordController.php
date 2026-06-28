<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\AccountDeactivatedException;
use App\Exceptions\AdminNotFoundException;
use App\Exceptions\OtpAlreadyIssuedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangePasswordRequest;
use App\Http\Requests\Admin\ForgotPasswordRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Http\Responses\ApiResponder;
use App\Notifications\Admin\AdminPasswordResetOtpNotification;
use App\Services\Admin\AdminAuthService;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;

class PasswordController extends Controller
{
    use ApiResponder;

    public function __construct(
        protected AdminAuthService $authService,
        protected OtpService $otpService,
    ) {}

    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->validated('email');
        // Always respond identically regardless of account state to prevent email enumeration.
        $genericMessage = 'If an admin account exists for that email, an OTP has been sent.';

        try {
            $user = $this->authService->findAdminForPasswordFlow($email);
        } catch (AdminNotFoundException|AccountDeactivatedException) {
            return $this->success(message: $genericMessage);
        }

        $this->authService->recordForgotPasswordRequested($user);

        try {
            $issued = $this->otpService->issue(OtpService::CHANNEL_EMAIL, $email, auditSubject: $user);
        } catch (OtpAlreadyIssuedException) {
            return $this->success(message: $genericMessage);
        }

        Notification::route('mail', $email)
            ->notify(new AdminPasswordResetOtpNotification($issued['otp']));

        return $this->success(message: $genericMessage);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        // Same response for every failure mode (unknown email, deactivated, bad/expired/consumed OTP)
        // to prevent enumeration. Only a true success returns 200 with the success message.
        $invalidResponse = $this->error('Invalid or expired OTP.', 422);

        try {
            $user = $this->authService->findAdminForPasswordFlow($data['email']);
        } catch (AdminNotFoundException|AccountDeactivatedException) {
            return $invalidResponse;
        }

        $isValid = $this->otpService->verify(
            channel: OtpService::CHANNEL_EMAIL,
            identifier: $data['email'],
            otp: $data['otp'],
            auditSubject: $user,
        );

        if (! $isValid) {
            return $invalidResponse;
        }

        $this->authService->applyNewPassword($user, $data['password']);
        $this->otpService->purge(OtpService::CHANNEL_EMAIL, $data['email']);

        return $this->success(message: 'Password reset successful. Please log in again.');
    }

    public function change(ChangePasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->authService->changePassword(
            user: $request->user(),
            newPassword: $data['password'],
            currentTokenId: $request->user()->currentAccessToken()->getKey(),
        );

        return $this->success(message: 'Password changed. Other sessions have been signed out.');
    }
}
