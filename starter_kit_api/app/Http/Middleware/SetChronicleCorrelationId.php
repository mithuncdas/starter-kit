<?php

namespace App\Http\Middleware;

use Chronicle\Facades\Chronicle;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Wraps every API request in a Chronicle transaction so every
 * Chronicle::record() call made while handling the request shares one
 * correlation_id. The id is echoed back on the response header so the
 * frontend can quote it in bug reports.
 */
class SetChronicleCorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        $correlationId = null;

        /** @var Response $response */
        $response = Chronicle::transaction(function () use ($next, $request, &$correlationId): Response {
            $correlationId = Chronicle::currentCorrelation();

            return $next($request);
        });

        if ($correlationId !== null) {
            $response->headers->set('X-Correlation-Id', $correlationId);
        }

        return $response;
    }
}
