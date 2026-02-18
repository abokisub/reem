<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IdempotencyMiddleware
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
        // Only apply to POST/PUT/PATCH/DELETE
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            // Require Idempotency-Key for sensitive routes
            if ($this->isSensitiveRoute($request)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Idempotency-Key header is required for this request.'
                ], 400);
            }
            return $next($request);
        }

        $requestHash = $this->calculateRequestHash($request);

        // Atomic Check & Lock
        return DB::transaction(function () use ($request, $next, $idempotencyKey, $requestHash) {
            $existing = DB::table('idempotency_keys')
                ->where('idempotency_key', $idempotencyKey)
                ->lockForUpdate() // PREVENT RACE CONDITIONS
                ->first();

            if ($existing) {
                // Verify hash matches
                if ($existing->request_hash !== $requestHash) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Idempotency-Key reuse detected with a different request payload.'
                    ], 422);
                }

                // Check expiry
                if (now()->greaterThan(\Illuminate\Support\Carbon::parse($existing->expires_at))) {
                    DB::table('idempotency_keys')->where('id', $existing->id)->delete();
                } else {
                    Log::info("🛡️ Idempotency: Key Hit [$idempotencyKey]");
                    return response()->json(
                        json_decode($existing->response_body, true),
                        $existing->status_code
                    )->header('Idempotency-Hit', 'true');
                }
            }

            // Proceed with request
            $response = $next($request);

            // Cache successful or client-error responses
            if ($response->status() < 500) {
                try {
                    DB::table('idempotency_keys')->updateOrInsert(
                        ['idempotency_key' => $idempotencyKey],
                        [
                            'request_hash' => $requestHash,
                            'response_body' => $response->getContent(),
                            'status_code' => $response->status(),
                            'expires_at' => now()->addHours(24),
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                } catch (\Exception $e) {
                    Log::error("🛡️ Idempotency Save Failed: " . $e->getMessage());
                }
            }

            return $response;
        });
    }

    protected function calculateRequestHash(Request $request)
    {
        $payload = json_encode([
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'body' => $request->all(),
        ]);

        return hash('sha256', $payload);
    }

    protected function isSensitiveRoute(Request $request)
    {
        // Merchant API routes are usually under api/v1
        $sensitivePatterns = [
            'api/v1/transfers*',
            'api/v1/refunds*',
            'api/v1/virtual-accounts*',
            'api/v1/settlements*',
            'api/v1/identity/verify*',
        ];

        foreach ($sensitivePatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
