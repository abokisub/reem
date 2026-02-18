<?php

namespace App\Http\Middleware\V1;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MerchantAuth
{
    public function handle(Request $request, Closure $next)
    {
        $authorization = $request->header('Authorization');

        if (!$authorization || !str_starts_with($authorization, 'Bearer ')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing or invalid Authorization header'
            ], 401);
        }

        $secretKey = substr($authorization, 7);
        $businessId = $request->header('x-business-id');
        $apiKey = $request->header('x-api-key');

        if (!$businessId || !$apiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing x-business-id or x-api-key header'
            ], 401);
        }

        // Find company by business_id first
        $company = Company::where('business_id', $businessId)->first();

        if (!$company) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid business ID'
            ], 401);
        }

        $isTest = false;

        // Check Live Credentials (using new unencrypted fields)
        if ($company->api_secret_key === $secretKey && $company->api_public_key === $apiKey) {
            $isTest = false;
        }
        // Check Sandbox Credentials
        elseif ($company->test_secret_key === $secretKey && $company->test_public_key === $apiKey) {
            $isTest = true;
        }
        else {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API credentials'
            ], 401);
        }

        if (!$isTest && !$company->isActive()) {
            return response()->json([
                'status' => 'error',
                'message' => 'API access is locked. Please unlock it in your dashboard.'
            ], 403);
        }

        // Attach company and mode to request
        $request->attributes->set('company', $company);
        $request->attributes->set('is_test', $isTest);
        $request->merge([
            'company_id' => $company->id,
            'is_test' => $isTest
        ]);

        return $next($request);
    }
}
