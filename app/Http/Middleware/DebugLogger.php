<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Debug Logger Middleware
 * Logs detailed information about requests for debugging
 */
class DebugLogger
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        
        // Log incoming request
        Log::channel('debug')->info('🔵 INCOMING REQUEST', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
            'body' => $this->sanitizeBody($request->all()),
            'timestamp' => now()->toDateTimeString(),
        ]);

        try {
            $response = $next($request);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            // Log successful response
            Log::channel('debug')->info('🟢 RESPONSE SUCCESS', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status' => $response->status(),
                'duration_ms' => $duration,
                'response_size' => strlen($response->getContent()),
                'timestamp' => now()->toDateTimeString(),
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            // Log error
            Log::channel('debug')->error('🔴 REQUEST ERROR', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'duration_ms' => $duration,
                'timestamp' => now()->toDateTimeString(),
            ]);
            
            throw $e;
        }
    }
    
    private function sanitizeHeaders(array $headers): array
    {
        $sensitive = ['authorization', 'x-api-key', 'x-business-id', 'cookie'];
        
        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $sensitive)) {
                $headers[$key] = ['***REDACTED***'];
            }
        }
        
        return $headers;
    }
    
    private function sanitizeBody(array $body): array
    {
        $sensitive = ['password', 'pin', 'secret', 'api_key', 'secret_key', 'bvn', 'account_number'];
        
        foreach ($body as $key => $value) {
            if (in_array(strtolower($key), $sensitive)) {
                $body[$key] = '***REDACTED***';
            }
        }
        
        return $body;
    }
}
