<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiRequestLogMiddleware
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
        $startTime = microtime(true);

        $response = $next($request);

        $endTime = microtime(true);
        $latency = (int) (($endTime - $startTime) * 1000);

        try {
            // Log the request
            \Illuminate\Support\Facades\DB::table('api_request_logs')->insert([
                'company_id' => $request->get('company_id'),
                'method' => $request->method(),
                'path' => $request->path(),
                'request_payload' => json_encode($this->maskSensitiveData($request->except(['password', 'pin', 'api_secret', 'secret']))),
                'response_payload' => $this->shouldLogResponse($request) ? substr($response->getContent(), 0, 10000) : null,
                'status_code' => $response->status(),
                'latency_ms' => $latency,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'is_test' => $request->get('is_test', false),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('🛡️ API Request Log Failed: ' . $e->getMessage());
        }

        return $response;
    }

    private function maskSensitiveData($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        $sensitiveFields = [
            'account_number',
            'bvn',
            'phone',
            'email',
            'pin',
            'password',
            'api_secret',
            'secret',
            'api_secret_key',
            'secret_key',
            'authorization',
            'x-api-key',
            'webhook_secret'
        ];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $data[$key] = '********';
            } elseif (is_array($value)) {
                $data[$key] = $this->maskSensitiveData($value);
            }
        }

        return $data;
    }

    private function shouldLogResponse(Request $request)
    {
        // Don't log huge responses or file downloads
        return true;
    }
}
