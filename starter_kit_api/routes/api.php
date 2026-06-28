<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserAddressController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:admin-login');

    // Public: the refresh token is read from an httpOnly cookie, not the
    // Authorization header, so this route cannot sit behind auth:sanctum.
    Route::post('refresh', [AuthController::class, 'refresh'])
        ->middleware('throttle:admin-refresh');

    Route::controller(PasswordController::class)->group(function (): void {
        Route::post('forgot-password', 'forgot')->middleware('throttle:admin-forgot');
        Route::post('reset-password', 'reset')->middleware('throttle:admin-reset');
    });

    // ability:access keeps refresh tokens (which only hold the "refresh"
    // ability) out of every business endpoint — they may only hit /refresh.
    Route::middleware(['auth:sanctum', 'ability:access', 'admin'])->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('change-password', [PasswordController::class, 'change']);

        Route::controller(ProfileController::class)->group(function (): void {
            Route::get('profile', 'show');
            Route::put('profile', 'update');
        });

        Route::get('permissions', [PermissionController::class, 'index'])
            ->middleware('permission:roles.view,sanctum');

        Route::controller(RoleController::class)->group(function (): void {
            Route::get('roles', 'index')->middleware('permission:roles.view,sanctum');
            Route::get('roles/active', 'activeRoles')->middleware('permission:admins.create|admins.update,sanctum');
            Route::post('roles', 'store')->middleware('permission:roles.create,sanctum');
            Route::get('roles/{role}', 'show')->middleware('permission:roles.view,sanctum');
            Route::put('roles/{role}', 'update')->middleware('permission:roles.update,sanctum');
            Route::delete('roles/{role}', 'destroy')->middleware('permission:roles.delete,sanctum');
        });

        Route::controller(AdminUserController::class)->group(function (): void {
            Route::get('admin-users', 'index')->middleware('permission:admins.view,sanctum');
            Route::post('admin-users', 'store')->middleware('permission:admins.create,sanctum');
            Route::get('admin-users/{admin_user}', 'show')->middleware('permission:admins.view,sanctum');
            Route::put('admin-users/{admin_user}', 'update')->middleware('permission:admins.update,sanctum');
            Route::delete('admin-users/{admin_user}', 'destroy')->middleware('permission:admins.delete,sanctum');
        });

        Route::controller(UserAddressController::class)
            ->prefix('admin-users/{admin_user}/addresses')
            ->scopeBindings()
            ->group(function (): void {
                Route::get('/', 'index')->middleware('permission:admins.view,sanctum');
                Route::post('/', 'store')->middleware('permission:admins.update,sanctum');
                Route::get('{address}', 'show')->middleware('permission:admins.view,sanctum');
                Route::put('{address}', 'update')->middleware('permission:admins.update,sanctum');
                Route::delete('{address}', 'destroy')->middleware('permission:admins.update,sanctum');
            });

        Route::prefix('locations')
            ->middleware('permission:locations.view,sanctum')
            ->controller(LocationController::class)
            ->group(function (): void {
                Route::get('countries', 'countries');
                Route::get('countries/{country}/structure', 'structure');
                Route::get('countries/{country}/top-level', 'topLevel');
                Route::get('countries/{country}/tree', 'tree');
                Route::get('areas/{area}/children', 'children');
            });

        Route::prefix('audit-logs')
            ->middleware('permission:audit.view,sanctum')
            ->controller(AuditLogController::class)
            ->group(function (): void {
                Route::get('/', 'index');
                Route::get('{entry}', 'show');
            });
    });
});
