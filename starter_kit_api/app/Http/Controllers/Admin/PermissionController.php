<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\GroupedPermissionResource;
use App\Http\Responses\ApiResponder;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    use ApiResponder;

    public function index(): JsonResponse
    {
        $permissions = Permission::query()->grouped()->get();

        return $this->success(
            data: GroupedPermissionResource::make($permissions),
            message: 'Permissions fetched.',
        );
    }
}
