<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MerchantRateLimiter
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $companyId = $request->get('company_id');

        if (!$companyId) {
            return $next($request);
        }

        $burstKey = 'rate_limit:burst:' . $companyId;
        $dailyKey = 'rate_limit:daily:' . $companyId;

        // Configuration (can be moved to config/pointpay.php)
        $burstAttempts = 5000; // Practically no limit for burst
        $dailyAttempts = 10000000; // 10 million daily limit

        // 1. Burst Check (Per Second)
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($burstKey, $burstAttempts)) {
            return $this->buildRateLimitResponse($burstKey);
        }

        // 2. Daily Check
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($dailyKey, $dailyAttempts)) {
            Log::alert('⛔ Enterprise Daily Limit Exhausted', ['company_id' => $companyId]);
            return $this->buildRateLimitResponse($dailyKey);
        }

        \Illuminate\Support\Facades\RateLimiter::hit($burstKey, 1);
        \Illuminate\Support\Facades\RateLimiter::hit($dailyKey, 86400);

        return $next($request);
    }

    protected function buildRateLimitResponse($key)
    {
        $retryAfter = \Illuminate\Support\Facades\RateLimiter::availableIn($key);

        return response()->json([
            'status' => 'error',
            'error_code' => 'too_many_requests',
            'message' => 'Rate limit exceeded. Please slow down.',
            'retry_after_seconds' => $retryAfter
        ], 429)->header('Retry-After', $retryAfter);
    }
}
