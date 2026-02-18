<?php

namespace App\Services\EaseID;

use App\Services\EaseID\EaseIDClient;
use Illuminate\Support\Facades\Log;

/**
 * EaseID KYC Service
 * 
 * Provides methods for identity verification using EaseID
 */
class EaseIDKycService
{
    private EaseIDClient $client;

    public function __construct()
    {
        $this->client = new EaseIDClient();
    }

    /**
     * Verify BVN (Enhanced)
     * 
     * @param string $bvn
     * @return array
     */
    public function verifyBvn(string $bvn): array
    {
        try {
            $response = $this->client->post('/api/validator-service/open/bvn/inquire', [
                'bvn' => $bvn,
            ]);

            return [
                'success' => true,
                'data' => $response['data'] ?? [],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Basic BVN Verification (Matching)
     * 
     * @param string $bvn
     * @param string $firstName
     * @param string $lastName
     * @param array $optionalParams (middleName, gender, birthday, phoneNumber)
     * @return array
     */
    public function verifyBvnBasic(string $bvn, string $firstName, string $lastName, array $optionalParams = []): array
    {
        try {
            $data = array_merge([
                'bvn' => $bvn,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'version' => 'V1.1',
            ], $optionalParams);

            $response = $this->client->post('/api/validator-service/open/bvn/verify', $data);

            return [
                'success' => true,
                'data' => $response['data'] ?? [],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify NIN (Enhanced)
     * 
     * @param string $nin
     * @return array
     */
    public function verifyNin(string $nin): array
    {
        try {
            $response = $this->client->post('/api/validator-service/open/nin/inquire', [
                'nin' => $nin,
            ]);

            return [
                'success' => true,
                'data' => $response['data'] ?? [],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify Bank Account
     * 
     * @param string $accountNumber
     * @param string $bankCode
     * @param string $bvn
     * @return array
     */
    public function verifyBankAccount(string $accountNumber, string $bankCode, string $bvn): array
    {
        try {
            $response = $this->client->post('/api/validator-service/open/bankAccount/verify', [
                'bankAccount' => $accountNumber,
                'bankCode' => $bankCode,
                'bvn' => $bvn,
            ]);

            return [
                'success' => true,
                'data' => $response['data'] ?? [],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check EaseID Wallet Balance
     * Use this for low-cost connectivity and signature verification
     * 
     * @return array
     */
    public function checkBalance(): array
    {
        try {
            $response = $this->client->post('/api/enquiry/balance', ['merchantId' => config('services.easeid.merchant_id')]);

            return [
                'success' => true,
                'balance' => $response['data']['balance'] ?? 0,
                'currency' => $response['data']['currency'] ?? 'NGN',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Initiate Liveness Detection (H5)
     * 
     * @param string $userId
     * @param string $notifyUrl
     * @param string $callbackUrl
     * @return array
     */
    public function verifyNinBasic(string $nin, string $firstName, string $lastName, array $optionalParams = []): array
    {
        try {
            $data = array_merge([
                'nin' => $nin,
                'firstName' => $firstName,
                'lastName' => $lastName,
                'version' => 'V1.1',
            ], $optionalParams);

            $response = $this->client->post('/api/validator-service/open/nin/verify', $data);

            return [
                'success' => true,
                'data' => $response['data'] ?? [],
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Initiate H5 Liveness Detection
     */
    public function initiateLiveness(string $userId, string $notifyUrl, string $callbackUrl): array
    {
        try {
            $response = $this->client->post('/api/validator-service/open/liveness/h5/initiate', [
                'userId' => $userId,
                'notifyUrl' => $notifyUrl,
                'callbackUrl' => $callbackUrl,
            ]);

            return [
                'success' => true,
                'url' => $response['data']['verifyUrl'] ?? null,
                'transactionId' => $response['data']['transactionId'] ?? null,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if phone/BVN/NIN is blacklisted
     * 
     * @param array $params ['phoneNumber' => '', 'bvnNo' => '', 'ninNo' => '']
     * @return array
     */
    public function checkBlacklist(array $params): array
    {
        try {
            // At least one parameter required
            if (empty($params['phoneNumber']) && empty($params['bvnNo']) && empty($params['ninNo'])) {
                throw new \InvalidArgumentException('At least one parameter (phoneNumber, bvnNo, ninNo) is required');
            }

            $requestData = array_filter($params);

            Log::info('Checking EaseID Blacklist', $requestData);

            $response = $this->client->post('/api/v1/okcard-risk-control/query/blacklist', $requestData);

            $isBlacklisted = ($response['data']['result'] ?? 'Don\'t hit') === 'hit';

            return [
                'success' => true,
                'blacklisted' => $isBlacklisted,
                'hit_time' => $response['data']['hitTime'] ?? null,
                'raw' => $response
            ];

        } catch (\Exception $e) {
            Log::error('Blacklist Check Failed', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Compare two face images
     * 
     * @param string $sourceImage Base64 encoded image
     * @param string $targetImage Base64 encoded image
     * @return array
     */
    public function compareFaces(string $sourceImage, string $targetImage): array
    {
        try {
            Log::info('Comparing Faces via EaseID');

            $response = $this->client->post('/api/easeid-kyc-service/facecapture/compare', [
                'sourceImage' => $sourceImage,
                'targetImage' => $targetImage,
            ]);

            $similarity = $response['data']['similarity'] ?? 0;
            $isMatch = $similarity > 60; // EaseID threshold

            return [
                'success' => true,
                'similarity' => $similarity,
                'is_match' => $isMatch,
                'raw' => $response
            ];

        } catch (\Exception $e) {
            Log::error('Face Comparison Failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get Credit Score
     * 
     * @param string $mobileNo
     * @param string $idNumber NIN or BVN
     * @param array $deviceInfo Optional device information
     * @return array
     */
    public function getCreditScore(string $mobileNo, string $idNumber, array $deviceInfo = []): array
    {
        try {
            $requestData = [
                'mobileNo' => $mobileNo,
                'idNumber' => $idNumber,
                'appId' => config('services.easeid.app_id'),
            ];

            if (!empty($deviceInfo)) {
                $requestData['extendInfo'] = $deviceInfo;
            }

            Log::info('Querying Credit Score', [
                'mobile' => $mobileNo,
                'id_number' => substr($idNumber, 0, 3) . '****'
            ]);

            $response = $this->client->post('/api/v1/okcard-risk-control/query/score', $requestData);

            return [
                'success' => true,
                'credit_score' => $response['data']['creditScore'] ?? 0,
                'credit_score_v3' => $response['data']['creditScoreV3'] ?? 0,
                'raw' => $response
            ];

        } catch (\Exception $e) {
            Log::error('Credit Score Query Failed', [
                'mobile' => $mobileNo,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}

