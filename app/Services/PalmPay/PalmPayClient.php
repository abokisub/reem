<?php

namespace App\Services\PalmPay;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\PalmPay\PalmPaySignature;

/**
 * PalmPay HTTP Client
 * 
 * Base client for making authenticated requests to PalmPay API
 */
class PalmPayClient
{
    private string $baseUrl;
    private string $merchantId;
    private string $appId;
    private PalmPaySignature $signature;

    public function __construct()
    {
        $this->baseUrl = config('services.palmpay.base_url');
        $this->merchantId = config('services.palmpay.merchant_id');
        $this->appId = config('services.palmpay.app_id') ?? $this->merchantId;
        $this->signature = new PalmPaySignature();
    }

    /**
     * Make a POST request to PalmPay API
     * 
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function post(string $endpoint, array $data): array
    {
        $this->checkCircuitBreaker();
        $startTime = microtime(true) * 1000;
        $response = null;

        try {
            // Add required parameters
            $data['requestTime'] = (int) (microtime(true) * 1000); // Precise milliseconds
            $data['version'] = $data['version'] ?? 'V2.0';
            $data['nonceStr'] = Str::random(32);

            // Generate signature
            $signature = $this->signature->generateSignature($data);

            // Prepare request
            $url = $this->baseUrl . $endpoint;

            Log::info('PalmPay API Request', [
                'url' => $url,
                'data' => $data,
                'signature' => $signature
            ]);

            // Make HTTP request
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'CountryCode' => 'NG',
                    'Authorization' => 'Bearer ' . $this->appId,
                    'Signature' => $signature,
                    'X-Request-ID' => request()->attributes->get('request_id'),
                    'X-Correlation-ID' => request()->attributes->get('correlation_id'),
                ])
                ->post($url, $data);

            // Log response
            Log::info('PalmPay API Response', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            // Check if request was successful
            if (!$response->successful()) {
                throw new \Exception('PalmPay API Error: ' . $response->body());
            }

            $responseData = $response->json();

            // Check PalmPay response code (can be 'code' or 'respCode')
            $respCode = $responseData['code'] ?? ($responseData['respCode'] ?? null);
            $successCodes = ['00000', '00000000'];

            if ($respCode !== null && !in_array($respCode, $successCodes)) {
                $respMsg = $responseData['message'] ?? ($responseData['respMsg'] ?? 'Unknown error');
                $this->recordFailure();
                throw new \Exception("PalmPay Error: {$respMsg} (Code: {$respCode})");
            }

            $this->recordSuccess();

            // Log successful response to DB
            $duration = (int) ((microtime(true) * 1000) - $startTime);
            $this->logToDatabase($endpoint, 'POST', $data, $responseData, $duration);

            return $responseData;

        } catch (\Exception $e) {
            $this->recordFailure();
            $duration = (int) ((microtime(true) * 1000) - $startTime);
            $this->logToDatabase($endpoint, 'POST', $data, $response ? $response->json() : 'No Response', $duration, $e->getMessage());

            $context = [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ];

            // If it's a whitelist error, try to help by logging the outbound IP
            if (str_contains($e->getMessage(), 'OPEN_GW_000012') || str_contains($e->getMessage(), 'white list')) {
                try {
                    $outboundIp = Http::get('https://ifconfig.me/ip')->body();
                    $context['detected_outbound_ip'] = trim($outboundIp);
                    Log::warning('PalmPay Whitelist Error Diagnostic: Server outbound IP detected.', $context);
                } catch (\Exception $ipEx) {
                    Log::warning('PalmPay Whitelist Error Diagnostic: Failed to detect outbound IP.', ['error' => $ipEx->getMessage()]);
                }
            }

            Log::error('PalmPay API Request Failed', $context);

            throw $e;
        }
    }

    /**
     * Make a GET request to PalmPay API
     * 
     * @param string $endpoint
     * @param array $params
     * @return array
     */
    public function get(string $endpoint, array $params = []): array
    {
        $this->checkCircuitBreaker();
        $startTime = microtime(true) * 1000;
        $response = null;

        try {
            // Add required parameters
            $params['requestTime'] = (int) (microtime(true) * 1000);
            $params['version'] = $params['version'] ?? 'V2.0';
            $params['nonceStr'] = Str::random(32);

            // Generate signature
            $signature = $this->signature->generateSignature($params);

            // Prepare URL
            $url = $this->baseUrl . $endpoint;

            Log::info('PalmPay API GET Request', [
                'url' => $url,
                'params' => $params
            ]);

            // Make HTTP request
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'CountryCode' => 'NG',
                    'Authorization' => 'Bearer ' . $this->appId,
                    'Signature' => $signature,
                    'X-Request-ID' => request()->attributes->get('request_id'),
                    'X-Correlation-ID' => request()->attributes->get('correlation_id'),
                ])
                ->get($url, $params);

            // Log response
            Log::info('PalmPay API GET Response', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if (!$response->successful()) {
                throw new \Exception('PalmPay API Error: ' . $response->body());
            }

            $responseData = $response->json();

            if (isset($responseData['code']) && $responseData['code'] !== '00000') {
                throw new \Exception(
                    'PalmPay Error: ' . ($responseData['message'] ?? 'Unknown error') .
                    ' (Code: ' . $responseData['code'] . ')'
                );
            }

            $duration = (int) ((microtime(true) * 1000) - $startTime);
            $this->logToDatabase($endpoint, 'GET', $params, $responseData, $duration);

            return $responseData;

        } catch (\Exception $e) {
            $duration = (int) ((microtime(true) * 1000) - $startTime);
            $this->logToDatabase($endpoint, 'GET', $params, $response ? $response->json() : 'No Response', $duration, $e->getMessage());

            Log::error('PalmPay API GET Request Failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Circuit Breaker Logic
     * 3 States: CLOSED, OPEN, HALF_OPEN
     */
    private function checkCircuitBreaker()
    {
        $cacheKey = 'palmpay_circuit_breaker';
        $state = \Illuminate\Support\Facades\Cache::get($cacheKey, 'CLOSED');

        if ($state === 'OPEN') {
            $lastFailureTime = \Illuminate\Support\Facades\Cache::get($cacheKey . '_time');

            // Re-test after 5 minutes (300 seconds)
            if ($lastFailureTime && now()->diffInSeconds($lastFailureTime) < 300) {
                throw new \Exception('PalmPay API Circuit Breaker: Connection OPEN (Service Unavailable).');
            }

            // Transition to HALF_OPEN to test 1 request
            \Illuminate\Support\Facades\Cache::put($cacheKey, 'HALF_OPEN');
            Log::info('🛡️ PalmPay Circuit Breaker: HALF-OPEN (Testing recovery)');
        }
    }

    private function recordFailure()
    {
        $cacheKey = 'palmpay_circuit_breaker';
        $failureCountKey = 'palmpay_failure_count';

        $state = \Illuminate\Support\Facades\Cache::get($cacheKey, 'CLOSED');

        if ($state === 'HALF_OPEN') {
            // If it fails in HALF_OPEN, go back to OPEN immediately
            \Illuminate\Support\Facades\Cache::put($cacheKey, 'OPEN', 600); // Wait another 10m
            \Illuminate\Support\Facades\Cache::put($cacheKey . '_time', now());
            Log::critical('🛡️ PalmPay Circuit Breaker: RE-OPENED (Recovery failed)');

            \App\Services\AlertService::trigger(
                'CIRCUIT_BREAKER_REOPEN',
                "PalmPay Circuit Breaker failed recovery and RE-OPENED.",
                ['state' => 'OPEN'],
                'critical'
            );
            return;
        }

        $failures = \Illuminate\Support\Facades\Cache::increment($failureCountKey);

        if ($failures >= 5) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, 'OPEN', 600);
            \Illuminate\Support\Facades\Cache::put($cacheKey . '_time', now());
            Log::critical('🛡️ PalmPay Circuit Breaker: OPENED (High failure rate)');

            \App\Services\AlertService::trigger(
                'CIRCUIT_BREAKER_OPEN',
                "PalmPay Circuit Breaker OPENED due to high failure rate (>= 5 failures).",
                ['failures' => $failures],
                'critical'
            );
        }
    }

    private function recordSuccess()
    {
        $cacheKey = 'palmpay_circuit_breaker';
        $state = \Illuminate\Support\Facades\Cache::get($cacheKey, 'CLOSED');

        if ($state === 'HALF_OPEN') {
            Log::info('🛡️ PalmPay Circuit Breaker: CLOSED (Recovery successful)');
        }

        \Illuminate\Support\Facades\Cache::forget('palmpay_failure_count');
        \Illuminate\Support\Facades\Cache::put($cacheKey, 'CLOSED');
    }

    /**
     * Permanent record of API interaction
     */
    private function logToDatabase(string $endpoint, string $method, array $request, $response, int $timeMs, ?string $error = null)
    {
        try {
            \Illuminate\Support\Facades\DB::table('provider_logs')->insert([
                'provider' => 'palmpay',
                'endpoint' => $endpoint,
                'method' => $method,
                'request_payload' => json_encode($request),
                'response_payload' => is_string($response) ? $response : json_encode($response),
                'status_code' => is_object($response) && method_exists($response, 'status') ? $response->status() : null,
                'response_time_ms' => $timeMs,
                'transaction_reference' => $request['orderNo'] ?? ($request['reference'] ?? ($request['accountReference'] ?? null)),
                'error' => $error,
                'ip_address' => request()->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to write to provider_logs: ' . $e->getMessage());
        }
    }
}