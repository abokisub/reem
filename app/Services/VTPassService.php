<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VTPassService
{
    private $baseUrl;
    private $username;
    private $password;

    public function __construct()
    {
        // Determine environment (sandbox or production)
        $environment = env('VTPASS_ENV', 'sandbox');

        $this->baseUrl = $environment === 'production'
            ? 'https://vtpass.com/api'
            : 'https://sandbox.vtpass.com/api';

        // Retrieve credentials from other_api table
        $apiConfig = DB::table('other_api')->first();
        $this->username = $apiConfig->vtpass_username ?? null;
        $this->password = $apiConfig->vtpass_password ?? null;
    }

    /**
     * Get authorization header
     */
    private function getAuthHeader()
    {
        return 'Basic ' . base64_encode($this->username . ':' . $this->password);
    }

    /**
     * Get service categories
     */
    public function getServiceCategories()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->getAuthHeader(),
            ])->get($this->baseUrl . '/service-categories');

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Failed to retrieve service categories',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('VTPass Service Categories Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get variation codes for a service
     * 
     * @param string $serviceID Service identifier (e.g., 'gotv', 'dstv', 'mtn-data')
     */
    public function getVariationCodes($serviceID)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->getAuthHeader(),
            ])->get($this->baseUrl . '/service-variations', [
                        'serviceID' => $serviceID
                    ]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Failed to retrieve variation codes',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('VTPass Variation Codes Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Purchase a service
     * 
     * @param array $payload Purchase payload
     */
    public function purchase($payload)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->getAuthHeader(),
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/pay', $payload);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Purchase failed',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('VTPass Purchase Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Requery transaction status
     * 
     * @param string $requestId Request ID from original transaction
     */
    public function requeryTransaction($requestId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->getAuthHeader(),
            ])->post($this->baseUrl . '/requery', [
                        'request_id' => $requestId
                    ]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Requery failed',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('VTPass Requery Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify customer (for smart card, meter number, etc.)
     * 
     * @param string $billersCode Customer identifier
     * @param string $serviceID Service identifier
     */
    public function verifyCustomer($billersCode, $serviceID)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->getAuthHeader(),
            ])->post($this->baseUrl . '/merchant-verify', [
                        'billersCode' => $billersCode,
                        'serviceID' => $serviceID
                    ]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Verification failed',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('VTPass Verify Customer Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get wallet balance
     */
    public function getBalance()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => $this->getAuthHeader(),
            ])->get($this->baseUrl . '/balance');

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Failed to retrieve balance',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('VTPass Balance Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Map VTPass response code to internal status
     * 
     * @param array $response VTPass API response
     * @return string Internal status (success, fail, process)
     */
    public static function mapResponseStatus($response)
    {
        if (empty($response)) {
            return null;
        }

        if (!isset($response['code'])) {
            return null;
        }

        $code = (string) $response['code'];

        switch ($code) {
            case '000':
                return 'success';
            case '099':
                return 'process'; // Transaction in progress
            default:
                return 'fail';
        }
    }

    /**
     * Get current environment
     */
    public function getEnvironment()
    {
        return env('VTPASS_ENV', 'sandbox');
    }

    /**
     * Get base URL
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }
}
