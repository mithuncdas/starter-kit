<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Http\Resources\Admin\AdminResource;
use App\Http\Responses\ApiResponder;
use App\Services\Admin\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponder;

    public function __construct(protected AdminAuthService $authService) {}

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing(['roles.permissions', 'permissions']);

        return $this->success(
            data: AdminResource::make($user),
            message: 'Profile fetched.',
        );
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = $this->authService->updateProfile(
            user: $request->user(),
            name: $data['name'],
            email: $data['email'],
        );

        return $this->success(
            data: AdminResource::make($user->load(['roles.permissions', 'permissions'])),
            message: 'Profile updated.',
        );
    }
}
