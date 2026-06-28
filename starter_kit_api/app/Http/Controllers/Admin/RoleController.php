<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\CannotModifyOwnRoleException;
use App\Exceptions\RoleStillInUseException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Role\IndexRoleRequest;
use App\Http\Requests\Admin\Role\StoreRoleRequest;
use App\Http\Requests\Admin\Role\UpdateRoleRequest;
use App\Http\Resources\Admin\RoleOptionResource;
use App\Http\Resources\Admin\RoleResource;
use App\Http\Responses\ApiResponder;
use App\Models\Role;
use App\Services\Admin\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use ApiResponder;

    public function __construct(protected RoleService $roleService) {}

    public function index(IndexRoleRequest $request): JsonResponse
    {
        $roles = Role::query()
            ->with('permissions')
            ->filter($request->validated())
            ->orderBy('id', 'desc')
            ->paginate($request->integer('per_page', 10))
            ->toResourceCollection(RoleResource::class);

        return $this->success(
            data: $roles,
            message: 'Roles fetched.',
        );
    }

    public function activeRoles(): JsonResponse
    {
        $roles = Role::query()
            ->active()
            ->orderBy('name')
            ->get();

        return $this->success(
            data: RoleOptionResource::collection($roles),
            message: 'Active roles fetched.',
        );
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roleService->create($request->validated(), actor: $request->user());

        return $this->success(
            data: RoleResource::make($role),
            message: 'Role created.',
            status: 201,
        );
    }

    public function show(Role $role): JsonResponse
    {
        return $this->success(
            data: RoleResource::make($role->load('permissions')),
            message: 'Role fetched.',
        );
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        try {
            $role = $this->roleService->update($role, $request->validated(), actor: $request->user());
        } catch (CannotModifyOwnRoleException $e) {
            return $this->error($e->getMessage(), 403);
        }

        return $this->success(
            data: RoleResource::make($role),
            message: 'Role updated.',
        );
    }

    public function destroy(Request $request, Role $role): JsonResponse
    {
        try {
            $this->roleService->delete($role, actor: $request->user());
        } catch (CannotModifyOwnRoleException $e) {
            return $this->error($e->getMessage(), 403);
        } catch (RoleStillInUseException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(message: 'Role deleted.');
    }
}
