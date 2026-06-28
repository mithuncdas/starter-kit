<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserAddress\StoreUserAddressRequest;
use App\Http\Requests\Admin\UserAddress\UpdateUserAddressRequest;
use App\Http\Resources\Admin\UserAddressResource;
use App\Http\Responses\ApiResponder;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Admin\UserAddressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    use ApiResponder;

    public function __construct(protected UserAddressService $service) {}

    public function index(User $adminUser): JsonResponse
    {
        $addresses = $adminUser->addresses()
            ->with(['adminArea.level', 'adminArea.ancestorsAndSelf.level'])
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        return $this->success(
            data: UserAddressResource::collection($addresses)->resolve(),
            message: 'Addresses fetched.',
        );
    }

    public function store(StoreUserAddressRequest $request, User $adminUser): JsonResponse
    {
        $address = $this->service->create(
            owner: $adminUser,
            data: $request->validated(),
            actor: $request->user(),
        );

        $address->load(['adminArea.level', 'adminArea.ancestorsAndSelf.level']);

        return $this->success(
            data: UserAddressResource::make($address)->resolve(),
            message: 'Address created.',
            status: 201,
        );
    }

    public function show(User $adminUser, UserAddress $address): JsonResponse
    {
        $address->load(['adminArea.level', 'adminArea.ancestorsAndSelf.level']);
        $address->setRelation('user', $adminUser);

        return $this->success(
            data: UserAddressResource::make($address)->resolve(),
            message: 'Address fetched.',
        );
    }

    public function update(UpdateUserAddressRequest $request, User $adminUser, UserAddress $address): JsonResponse
    {
        $address = $this->service->update(
            owner: $adminUser,
            address: $address,
            data: $request->validated(),
            actor: $request->user(),
        );

        $address->load(['adminArea.level', 'adminArea.ancestorsAndSelf.level']);

        return $this->success(
            data: UserAddressResource::make($address)->resolve(),
            message: 'Address updated.',
        );
    }

    public function destroy(Request $request, User $adminUser, UserAddress $address): JsonResponse
    {
        $this->service->delete(
            owner: $adminUser,
            address: $address,
            actor: $request->user(),
        );

        return $this->success(message: 'Address deleted.');
    }
}
