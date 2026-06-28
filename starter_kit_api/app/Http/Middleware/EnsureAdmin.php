<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    use ApiResponder;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isAdmin()) {
            return $this->error('Admin access required.', 403);
        }

        if (! $user->isActive()) {
            return $this->error('Account is deactivated, please contact administrator.', 403);
        }

        return $next($request);
    }
}
