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
            'appId' => $this->appId,
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
            'appId' => $this->appId,
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
     * Based on working EaseID implementation
     * 
     * Steps:
     * 1. Sort parameters by key (ASCII order)
     * 2. Concatenate as key=value&key=value format
     * 3. Calculate MD5 hash (UPPERCASE)
     * 4. Sign MD5 hash with RSA-SHA1 (not SHA256!)
     * 5. Base64 encode
     */
    protected function generateSignature(array $params): string
    {
        // Remove null/empty values
        $params = array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        });

        // Sort by key (ASCII dictionary order)
        ksort($params);

        // Concatenate key=value pairs with & delimiter
        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }
        $signString = implode('&', $pairs);

        // Calculate MD5 hash (UPPERCASE - this is critical!)
        $md5Hash = strtoupper(md5($signString));

        Log::info('EaseID Signature Generation', [
            'sign_string' => $signString,
            'md5_hash' => $md5Hash
        ]);

        // RSA sign the MD5 hash with SHA1 (not SHA256!)
        $privateKey = openssl_pkey_get_private($this->formatPrivateKey($this->privateKey));

        if (!$privateKey) {
            throw new Exception('Invalid RSA private key');
        }

        openssl_sign($md5Hash, $signature, $privateKey, OPENSSL_ALGO_SHA1);

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

    /**
     * Face Recognition - Compare two face images
     * Returns similarity score (>60 = same person)
     */
    public function compareFaces(string $sourceImage, string $targetImage): array
    {
        $endpoint = '/api/easeid-kyc-service/facecapture/compare';

        $requestData = [
            'appId' => $this->appId,
            'sourceImage' => $sourceImage,
            'targetImage' => $targetImage,
            'requestTime' => $this->getRequestTime(),
            'version' => 'V1.1',
            'nonceStr' => $this->generateNonceStr(),
        ];

        Log::info('EaseID Face Recognition Request');

        return $this->makeRequest('POST', $endpoint, $requestData);
    }

    /**
     * Initialize Liveness Detection (H5)
     * Returns URL for user to perform liveness check
     */
    public function initializeLiveness(string $bizId, string $redirectUrl, ?string $userId = null): array
    {
        $endpoint = '/api/easeid-kyc-service/facecapture/h5/initialize';

        $requestData = [
            'appId' => $this->appId,
            'bizId' => $bizId,
            'redirectUrl' => $redirectUrl,
            'metaInfo' => '1',
            'serviceLevel' => 1,
            'secureLevel' => '1',
            'requestTime' => $this->getRequestTime(),
            'version' => 'V1.1',
            'nonceStr' => $this->generateNonceStr(),
        ];

        if ($userId) {
            $requestData['userId'] = $userId;
        }

        Log::info('EaseID Liveness Initialize Request', ['bizId' => $bizId]);

        return $this->makeRequest('POST', $endpoint, $requestData);
    }

    /**
     * Query Liveness Detection Result
     */
    public function queryLivenessResult(string $transactionId): array
    {
        $endpoint = '/api/easeid-kyc-service/facecapture/query';

        $requestData = [
            'appId' => $this->appId,
            'transactionId' => $transactionId,
            'requestTime' => $this->getRequestTime(),
            'version' => 'V1.1',
            'nonceStr' => $this->generateNonceStr(),
        ];

        Log::info('EaseID Liveness Query Request', ['transactionId' => $transactionId]);

        return $this->makeRequest('POST', $endpoint, $requestData);
    }

    /**
     * Check Blacklist
     * Check if customer is on credit blacklist
     */
    public function checkBlacklist(?string $phoneNumber = null, ?string $bvn = null, ?string $nin = null): array
    {
        $endpoint = '/api/v1/okcard-risk-control/query/blacklist';

        $requestData = [
            'appId' => $this->appId,
            'requestTime' => $this->getRequestTime(),
            'version' => 'V1.1',
            'nonceStr' => $this->generateNonceStr(),
        ];

        if ($phoneNumber) $requestData['phoneNumber'] = $phoneNumber;
        if ($bvn) $requestData['bvnNo'] = $bvn;
        if ($nin) $requestData['ninNo'] = $nin;

        Log::info('EaseID Blacklist Check Request');

        return $this->makeRequest('POST', $endpoint, $requestData);
    }

    /**
     * Credit Score (Nigeria)
     * Get customer credit score
     */
    public function getCreditScoreNigeria(string $mobileNo, string $idNumber): array
    {
        $endpoint = '/api/v1/okcard-risk-control/query/score';

        $requestData = [
            'appId' => $this->appId,
            'mobileNo' => $mobileNo,
            'idNumber' => $idNumber,
            'requestTime' => $this->getRequestTime(),
            'version' => 'V1.1',
            'nonceStr' => $this->generateNonceStr(),
        ];

        Log::info('EaseID Credit Score (NG) Request');

        return $this->makeRequest('POST', $endpoint, $requestData);
    }

    /**
     * Credit Score (Tanzania)
     */
    public function getCreditScoreTanzania(string $gaid, string $phoneNumber): array
    {
        $endpoint = '/api/v1/risk-score/query/score';

        $requestData = [
            'appId' => $this->appId,
            'gaid' => $gaid,
            'phoneNumber' => $phoneNumber,
            'requestTime' => $this->getRequestTime(),
            'version' => 'V1.1',
            'nonceStr' => $this->generateNonceStr(),
        ];

        Log::info('EaseID Credit Score (TZ) Request');

        $response = $this->makeRequest('POST', $endpoint, $requestData);
        
        // Change country code for Tanzania
        return $response;
    }

    /**
     * Loan Features Query (Nigeria)
     */
    public function getLoanFeatures(string $value, int $type = 1, string $accessType = '01'): array
    {
        $endpoint = '/api/v1/group';

        $requestData = [
            'type' => $type,
            'accessType' => $accessType,
            'value' => $value,
            'encrypt' => '0',
            'requestTime' => $this->getRequestTime(),
            'version' => 'V1.1',
            'nonceStr' => $this->generateNonceStr(),
        ];

        Log::info('EaseID Loan Features Request');

        return $this->makeRequest('POST', $endpoint, $requestData);
    }

    /**
     * Query EaseID Account Balance
     */
    public function getBalance(): array
    {
        $endpoint = '/api/enquiry/balance';

        $requestData = [
            'appId' => $this->appId,
            'requestTime' => $this->getRequestTime(),
            'version' => 'V1.1',
            'nonceStr' => $this->generateNonceStr(),
        ];

        Log::info('EaseID Balance Query Request');

        return $this->makeRequest('POST', $endpoint, $requestData);
    }
}
