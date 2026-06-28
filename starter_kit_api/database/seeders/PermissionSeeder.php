<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $groups = [
            'Roles' => [
                'roles.view',
                'roles.create',
                'roles.update',
                'roles.delete',
            ],
            'Admins' => [
                'admins.view',
                'admins.create',
                'admins.update',
                'admins.delete',
            ],
            'Users' => [
                'users.view',
                'users.update',
            ],
            'Locations' => [
                'locations.view',
            ],
            'Audit' => [
                'audit.view',
            ],
        ];

        foreach ($groups as $group => $names) {
            foreach ($names as $name) {
                Permission::query()->updateOrCreate(
                    ['name' => $name, 'guard_name' => 'sanctum'],
                    ['group' => $group],
                );
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
