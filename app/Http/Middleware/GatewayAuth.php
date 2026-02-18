<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Gateway API Authentication Middleware
 * 
 * Implements dual-header authentication (Xixapay-style)
 * Requires both Authorization header and X-API-Key header
 */
class GatewayAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Get headers
            $authorization = $request->header('Authorization');
            $apiKey = $request->header('X-API-Key');
            $businessId = $request->header('X-Business-ID');

            // Check if headers are present
            if (!$authorization || !$apiKey || !$businessId) {
                return $this->unauthorizedResponse('Missing authentication headers (Authorization, X-API-Key, or X-Business-ID)');
            }

            // Extract Bearer token
            if (!str_starts_with($authorization, 'Bearer ')) {
                return $this->unauthorizedResponse('Invalid authorization format');
            }

            $secretKey = substr($authorization, 7); // Remove "Bearer "

            // Find company by Live API credentials
            $company = Company::where('business_id', $businessId)
                ->where('api_secret_key', $secretKey)
                ->where('api_key', $apiKey)
                ->first();

            $isTest = false;

            // If not found, try Sandbox credentials
            if (!$company) {
                $company = Company::where('business_id', $businessId)
                    ->where('test_secret_key', $secretKey)
                    ->where('test_api_key', $apiKey)
                    ->first();

                if ($company) {
                    $isTest = true;
                }
            }

            if (!$company) {
                Log::warning('Gateway Auth Failed: Invalid credentials', [
                    'ip' => $request->ip(),
                    'endpoint' => $request->path()
                ]);

                return $this->unauthorizedResponse('Invalid credentials');
            }

            // Check if company is active
            if (!$isTest && !$company->isActive()) {
                Log::warning('Gateway Auth Failed: API locked', [
                    'company_id' => $company->id,
                    'status' => $company->status
                ]);

                return $this->unauthorizedResponse('API access is locked. Please unlock it in your dashboard.', 'API_LOCKED');
            }

            // Attach company and mode to request
            $request->attributes->set('company', $company);
            $request->attributes->set('is_test', $isTest);

            // Physical Database Isolation
            if ($isTest) {
                \Illuminate\Support\Facades\Config::set('database.default', 'sandbox');
                \Illuminate\Support\Facades\DB::purge('mysql'); // Purge main connection to prevent cross-bleeding
                \Illuminate\Support\Facades\DB::reconnect('sandbox');

                Log::info('🏗️ Sandbox Isolation Active', [
                    'database' => config('database.connections.sandbox.database')
                ]);
            }

            $request->merge([
                'company_id' => $company->id,
                'is_test' => $isTest
            ]);

            Log::info('Gateway Auth Success', [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'mode' => $isTest ? 'TEST' : 'LIVE',
                'endpoint' => $request->path()
            ]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('Gateway Auth Error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip()
            ]);

            return $this->unauthorizedResponse('Authentication failed');
        }
    }

    /**
     * Return unauthorized response
     */
    private function unauthorizedResponse(string $message, string $code = 'UNAUTHORIZED')
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => $code
        ], 401);
    }
}