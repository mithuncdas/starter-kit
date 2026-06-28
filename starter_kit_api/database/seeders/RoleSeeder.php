<?php

namespace Database\Seeders;

use App\Enums\RoleStatusEnum;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $role = Role::query()->updateOrCreate(
            ['name' => 'Administrator', 'guard_name' => 'sanctum'],
            ['status' => RoleStatusEnum::Active],
        );

        $role->syncPermissions(Permission::query()->get());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
