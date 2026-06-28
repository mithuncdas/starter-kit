<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\AccountDeactivatedException;
use App\Exceptions\InvalidCredentialsException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Resources\Admin\AdminResource;
use App\Http\Responses\ApiResponder;
use App\Services\Admin\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponder;

    public function __construct(protected AdminAuthService $authService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $result = $this->authService->login(
                email: $data['email'],
                password: $data['password'],
                deviceName: $data['device_name'] ?? null,
            );
        } catch (InvalidCredentialsException $e) {
            return $this->error($e->getMessage(), 422);
        } catch (AccountDeactivatedException $e) {
            return $this->error($e->getMessage(), 403);
        }

        return $this->success(
            data: [
                'token' => $result['token'],
                'admin' => AdminResource::make($result['user']),
            ],
            message: 'Login successful.',
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout(
            user: $request->user(),
            token: $request->user()->currentAccessToken(),
        );

        return $this->success(message: 'Logged out.');
    }
}
