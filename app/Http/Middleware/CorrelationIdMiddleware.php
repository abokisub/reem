<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorrelationIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $requestId = $request->header('X-Request-ID') ?? (string) \Illuminate\Support\Str::uuid();
        $correlationId = $request->header('X-Correlation-ID') ?? $requestId;

        // Set on request context
        $request->attributes->set('request_id', $requestId);
        $request->attributes->set('correlation_id', $correlationId);

        $response = $next($request);

        // Return headers for client-side tracing
        $response->headers->set('X-Request-ID', $requestId);
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
