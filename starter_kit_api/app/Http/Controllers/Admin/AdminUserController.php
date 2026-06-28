<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CannotModifySelfException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminUser\IndexAdminUserRequest;
use App\Http\Requests\Admin\AdminUser\StoreAdminUserRequest;
use App\Http\Requests\Admin\AdminUser\UpdateAdminUserRequest;
use App\Http\Resources\Admin\AdminUserResource;
use App\Http\Responses\ApiResponder;
use App\Models\User;
use App\Services\Admin\AdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    use ApiResponder;

    public function __construct(protected AdminUserService $service) {}

    public function index(IndexAdminUserRequest $request): JsonResponse
    {
        $admins = User::query()
            ->admins()
            ->with(['roles.permissions', 'permissions'])
            ->filter($request->validated())
            ->orderBy('id', 'desc')
            ->paginate($request->integer('per_page', 10))
            ->toResourceCollection(AdminUserResource::class);

        return $this->success(
            data: $admins,
            message: 'Admins fetched.',
        );
    }

    public function store(StoreAdminUserRequest $request): JsonResponse
    {
        $user = $this->service->create($request->validated(), actor: $request->user());

        return $this->success(
            data: AdminUserResource::make($user),
            message: 'Admin created.',
            status: 201,
        );
    }

    public function show(User $adminUser): JsonResponse
    {
        return $this->success(
            data: AdminUserResource::make($adminUser->load(['roles.permissions', 'permissions'])),
            message: 'Admin fetched.',
        );
    }

    public function update(UpdateAdminUserRequest $request, User $adminUser): JsonResponse
    {
        try {
            $user = $this->service->update($adminUser, $request->validated(), actor: $request->user());
        } catch (CannotModifySelfException $e) {
            return $this->error($e->getMessage(), 403);
        }

        return $this->success(
            data: AdminUserResource::make($user),
            message: 'Admin updated.',
        );
    }

    public function destroy(Request $request, User $adminUser): JsonResponse
    {
        try {
            $this->service->delete($adminUser, actor: $request->user());
        } catch (CannotModifySelfException $e) {
            return $this->error($e->getMessage(), 403);
        }

        return $this->success(message: 'Admin deleted.');
    }
}
