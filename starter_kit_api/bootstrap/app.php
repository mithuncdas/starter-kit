<?php

use App\Exceptions\AccountDeactivatedException;
use App\Exceptions\AdminNotFoundException;
use App\Exceptions\CannotModifyOwnRoleException;
use App\Exceptions\CannotModifySelfException;
use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\OtpAlreadyIssuedException;
use App\Exceptions\RoleStillInUseException;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\SetChronicleCorrelationId;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            SetChronicleCorrelationId::class,
        ]);

        $middleware->alias([
            'admin' => EnsureAdmin::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Force JSON responses for any /api/* path even if the client forgot Accept: application/json.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );

        $apiError = fn (string $message, int $status): JsonResponse => response()->json([
            'success' => false,
            'message' => $message,
            'errors' => null,
        ], $status);

        $exceptions->render(fn (UnauthorizedException $e, Request $request) => $apiError(
            'You do not have permission to perform this action.', 403
        ));

        // Defensive fallbacks: controllers normally catch these, but if any path forgets to,
        // the exception still returns a consistent JSON envelope rather than an HTML error page.
        $exceptions->render(fn (InvalidCredentialsException $e) => $apiError($e->getMessage(), 422));
        $exceptions->render(fn (AccountDeactivatedException $e) => $apiError($e->getMessage(), 403));
        $exceptions->render(fn (AdminNotFoundException $e) => $apiError($e->getMessage(), 422));
        $exceptions->render(fn (OtpAlreadyIssuedException $e) => $apiError($e->getMessage(), 422));
        $exceptions->render(fn (CannotModifySelfException $e) => $apiError($e->getMessage(), 403));
        $exceptions->render(fn (CannotModifyOwnRoleException $e) => $apiError($e->getMessage(), 403));
        $exceptions->render(fn (RoleStillInUseException $e) => $apiError($e->getMessage(), 422));
    })->create();
