<?php

namespace App\Services\EaseID;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\EaseID\EaseIDSignature;

/**
 * EaseID HTTP Client
 * 
 * Base client for making authenticated requests to EaseID API
 */
class EaseIDClient
{
    private string $baseUrl;
    private string $merchantId;
    private string $appId;
    private EaseIDSignature $signature;

    public function __construct()
    {
        $this->baseUrl = config('services.easeid.base_url', 'https://open-api.easeid.ai');
        $this->merchantId = config('services.easeid.merchant_id');
        $this->appId = config('services.easeid.app_id');
        $this->signature = new EaseIDSignature();
    }

    /**
     * Make a POST request to EaseID API
     * 
     * @param string $endpoint
     * @param array $data
     * @return array
     */
    public function post(string $endpoint, array $data): array
    {
        try {
            // Add required parameters
            $data['requestTime'] = (int) (microtime(true) * 1000);
            $data['version'] = 'V1.1';
            $data['nonceStr'] = Str::random(32);
            $data['appId'] = $this->appId;

            // Generate signature
            $signature = $this->signature->generateSignature($data);

            // Prepare request
            $url = rtrim($this->baseUrl, '/') . '/' . ltrim($endpoint, '/');

            Log::info('EaseID API Request', [
                'url' => $url,
                'data' => $data,
                'signature' => $signature
            ]);

            // Make HTTP request
            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->appId,
                    'Signature' => $signature,
                    'CountryCode' => 'NG',
                ])
                ->post($url, $data);

            // Log response
            Log::info('EaseID API Response', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if (!$response->successful()) {
                throw new \Exception('EaseID API HTTP Error: ' . $response->status() . ' ' . $response->body());
            }

            $responseData = $response->json();

            // EaseID uses respCode and respMsg
            $respCode = $responseData['respCode'] ?? ($responseData['code'] ?? null);

            // Support multiple formats of success codes (00000, 00000000, etc.)
            $successCodes = ['00000', '00000000', '0', '0000'];

            if (!in_array($respCode, $successCodes)) {
                $respMsg = $responseData['respMsg'] ?? ($responseData['message'] ?? 'Unknown error');
                throw new \Exception("EaseID Error: {$respMsg} (Code: {$respCode})");
            }

            return $responseData;

        } catch (\Exception $e) {
            Log::error('EaseID API Request Failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
