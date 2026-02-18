<?php

namespace App\Services\KYC;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

/**
 * EaseID API Client
 * Handles BVN, NIN, and other KYC verifications via EaseID.ai
 */
class EaseIdClient
{
    protected $appId;
    protected $merchantId;
    protected $privateKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->appId = config('services.easeid.app_id');
        $this->merchantId = config('services.easeid.merchant_id');
        $this->privateKey = config('services.easeid.private_key');
        $this->baseUrl = config('services.easeid.base_url');

        if (!$this->appId || !$this->privateKey || !$this->baseUrl) {
            throw new Exception('EaseID credentials not configured');
        }
    }

    /**
     * Enhanced BVN Inquiry
     * Verifies Bank Verification Number and returns detailed information
     */
    public function verifyBVN(string $bvn): array
    {
        $endpoint = '/api/validator-service/open/bvn/inquire';

        $requestData = [
            'bvn' => $bvn,
            'requestTime' => $this->getRequestTime(),
            'version' => 'V1.1',
            'nonceStr' => $this->generateNonceStr(),
        ];

        Log::info('EaseID BVN Verification Request', [
            'bvn' => substr($bvn, 0, 3) . '****' . substr($bvn, -2),
            'endpoint' => $endpoint,
        ]);

        return $this->makeRequest('POST', $endpoint, $requestData);
    }

    /**
     * Basic BVN Verify
     * Simple BVN verification with name matching
     */
    public function verifyBVNBasic(string $bvn, string $firstName, string $lastName, ?string $middleName = null): array
    {
        $endpoint = '/api/validator-service/open/bvn/verify';

        $requestData = [
            'bvn' => $bvn,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'requestTime' => $this->getRequestTime(),
            'version' => 'V1.1',
            'nonceStr' => $this->generateNonceStr(),
        ];

        if ($middleName) {
            $requestData['middleName'] = $middleName;
        }

        Log::info('EaseID Basic BVN Verification Request', [
            'bvn' => substr($bvn, 0, 3) . '****' . substr($bvn, -2),
            'name' => "$firstName $lastName",
        ]);

        return $this->makeRequest('POST', $endpoint, $requestData);
    }

    /**
     * NIN Inquiry
     * Verifies National Identification Number
     */
    public function verifyNIN(string $nin): array
    {
        $endpoint = '/api/validator-service/open/nin/inquire';

        $requestData = [
            'nin' => $nin,
            'requestTime' => $this->getRequestTime(),
            'version' => 'V1.1',
            'nonceStr' => $this->generateNonceStr(),
        ];

        Log::info('EaseID NIN Verification Request', [
            'nin' => substr($nin, 0, 3) . '****' . substr($nin, -2),
        ]);

        return $this->makeRequest('POST', $endpoint, $requestData);
    }

    /**
     * Basic NIN Verify
     * Simple NIN verification with name matching
     */
    public function verifyNINBasic(string $nin, string $firstName, string $lastName, ?string $middleName = null): array
    {
        $endpoint = '/api/validator-service/open/nin/verify';

        $requestData = [
            'nin' => $nin,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'requestTime' => $this->getRequestTime(),
            'version' => 'V1.1',
            'nonceStr' => $this->generateNonceStr(),
        ];

        if ($middleName) {
            $requestData['middleName'] = $middleName;
        }

        Log::info('EaseID Basic NIN Verification Request', [
            'nin' => substr($nin, 0, 3) . '****' . substr($nin, -2),
            'name' => "$firstName $lastName",
        ]);

        return $this->makeRequest('POST', $endpoint, $requestData);
    }

    /**
     * Bank Account Verification
     * Verifies bank account ownership
     */
    public function verifyBankAccount(string $accountNumber, string $bankCode): array
    {
        $endpoint = '/api/validator-service/open/bank-account/verify';

        $requestData = [
            'accountNumber' => $accountNumber,
            'bankCode' => $bankCode,
            'requestTime' => $this->getRequestTime(),
            'version' => 'V1.1',
            'nonceStr' => $this->generateNonceStr(),
        ];

        Log::info('EaseID Bank Account Verification Request', [
            'account' => substr($accountNumber, 0, 3) . '****' . substr($accountNumber, -2),
            'bank_code' => $bankCode,
        ]);

        return $this->makeRequest('POST', $endpoint, $requestData);
    }

    /**
     * Make HTTP request to EaseID API
     */
    protected function makeRequest(string $method, string $endpoint, array $data): array
    {
        try {
            $signature = $this->generateSignature($data);
            $url = $this->baseUrl . $endpoint;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->appId,
                'Signature' => $signature,
                'CountryCode' => 'NG',
                'Content-Type' => 'application/json',
            ])->post($url, $data);

            $responseData = $response->json();

            Log::info('EaseID API Response', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'success' => $responseData['success'] ?? false,
            ]);

            if (!$response->successful()) {
                throw new Exception('EaseID API request failed: ' . ($responseData['message'] ?? 'Unknown error'));
            }

            return [
                'success' => $responseData['success'] ?? false,
                'message' => $responseData['message'] ?? '',
                'data' => $responseData['data'] ?? null,
                'raw' => $responseData,
            ];

        } catch (Exception $e) {
            Log::error('EaseID API Error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Generate signature for EaseID API
     * Algorithm: RSA encryption of MD5 hash
     * 
     * Steps:
     * 1. Sort parameters by key (ASCII order)
     * 2. Concatenate as key=value pairs (no delimiters)
     * 3. Calculate MD5 hash
     * 4. Encrypt MD5 hash with RSA private key
     */
    protected function generateSignature(array $params): string
    {
        // Remove null/empty values
        $params = array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        });

        // Sort by key (ASCII dictionary order)
        ksort($params);

        // Concatenate key=value pairs without delimiters
        $signString = '';
        foreach ($params as $key => $value) {
            $signString .= $key . '=' . $value;
        }

        // Calculate MD5 hash
        $md5Hash = md5($signString);

        // RSA encrypt the MD5 hash
        $privateKey = openssl_pkey_get_private($this->formatPrivateKey($this->privateKey));

        if (!$privateKey) {
            throw new Exception('Invalid RSA private key');
        }

        openssl_sign($md5Hash, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        // Base64 encode the signature
        return base64_encode($signature);
    }

    /**
     * Format private key with proper PEM headers
     */
    protected function formatPrivateKey(string $key): string
    {
        if (strpos($key, '-----BEGIN') !== false) {
            return $key;
        }

        return "-----BEGIN PRIVATE KEY-----\n" .
            chunk_split($key, 64, "\n") .
            "-----END PRIVATE KEY-----";
    }

    /**
     * Get current request time in milliseconds
     */
    protected function getRequestTime(): int
    {
        return (int) (microtime(true) * 1000);
    }

    /**
     * Generate 32-character random nonce string
     */
    protected function generateNonceStr(): string
    {
        return Str::random(32);
    }
}
