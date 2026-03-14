<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * KoboPoint API Service
 * 
 * Complete integration for all KoboPoint services:
 * - Airtime Purchase
 * - Data Purchase  
 * - Cable TV Subscription
 * - Electricity Bill Payment
 * - Education PIN Purchase
 * - Bulk SMS
 * - Airtime to Cash
 * - Recharge Card Printing
 * - Data Card Printing
 */
class KobopointService
{
    private $baseUrl;
    private $username;
    private $password;
    private $token;
    private $pin;

    public function __construct()
    {
        $this->baseUrl = 'https://kobopoint.com/api';
        
        // Get credentials from other_api table (same as other services)
        $other_api = \DB::table('other_api')->first();
        
        if (!$other_api) {
            Log::error('KoboPoint: other_api table is empty');
            throw new \Exception('KoboPoint configuration not found');
        }
        
        $this->username = $other_api->kobopoint_username ?? 'Habukhan';
        $this->password = $other_api->kobopoint_password ?? '@Habukhan12';
        
        // Use custom URL if set
        if (!empty($other_api->kobopoint_url)) {
            $this->baseUrl = rtrim($other_api->kobopoint_url, '/');
        }
        
        Log::info('KoboPoint Service initialized', [
            'username' => $this->username,
            'base_url' => $this->baseUrl
        ]);
    }

    /**
     * Authenticate and get access token
     */
    private function authenticate()
    {
        try {
            $response = Http::timeout(30)->post($this->baseUrl . '/login/verify/user', [
                'username' => $this->username,
                'password' => $this->password
            ]);

            $data = $response->json();
            
            Log::info('KoboPoint Authentication Response', [
                'status_code' => $response->status(),
                'response' => $data
            ]);

            if ($data && isset($data['status']) && $data['status'] === 'success') {
                $this->token = $data['token'];
                
                // Cache token for 30 minutes
                Cache::put('kobopoint_token', $this->token, 1800);
                
                Log::info('KoboPoint: Authentication successful');
                return true;
            }

            Log::error('KoboPoint: Authentication failed', ['response' => $data]);
            return false;

        } catch (\Exception $e) {
            Log::error('KoboPoint: Authentication error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get current transaction PIN (required for all purchases)
     */
    private function getCurrentPin()
    {
        try {
            if (!$this->token) {
                if (!$this->authenticate()) {
                    throw new \Exception('Authentication failed');
                }
            }

            $response = Http::timeout(30)->get($this->baseUrl . "/account/pin/{$this->token}");
            $data = $response->json();

            if ($data['status'] === 'success') {
                $this->pin = $data['pin'];
                Log::info('KoboPoint: PIN retrieved successfully');
                return $this->pin;
            }

            throw new \Exception('Failed to retrieve PIN: ' . ($data['message'] ?? 'Unknown error'));

        } catch (\Exception $e) {
            Log::error('KoboPoint: PIN retrieval error', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Make authenticated API request
     */
    private function makeRequest($method, $endpoint, $data = [])
    {
        try {
            // Check if we have a cached token
            $this->token = Cache::get('kobopoint_token');
            
            if (!$this->token) {
                if (!$this->authenticate()) {
                    throw new \Exception('Authentication failed');
                }
            }

            // Get fresh PIN for transactions
            if (in_array($endpoint, ['/topup', '/data', '/bill', '/cable', '/exam', '/bulksms', '/cash', '/recharge_card', '/data_card'])) {
                $data['pin'] = $this->getCurrentPin();
                $data['user_id'] = $this->token;
            }

            $url = $this->baseUrl . $endpoint;
            
            if ($method === 'GET') {
                $response = Http::timeout(30)->get($url, $data);
            } else {
                $response = Http::timeout(30)->post($url, $data);
            }

            $result = $response->json();

            Log::info('KoboPoint API Request', [
                'endpoint' => $endpoint,
                'method' => $method,
                'status' => $result['status'] ?? 'unknown'
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('KoboPoint API Error', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    // ==================== AIRTIME SERVICES ====================

    /**
     * Get network providers
     */
    public function getNetworks()
    {
        return $this->makeRequest('GET', '/website/app/network');
    }

    /**
     * Auto-detect network from phone number
     */
    public function detectNetwork($phoneNumber)
    {
        return $this->makeRequest('GET', "/verify/network/{$phoneNumber}/habukhan/system");
    }

    /**
     * Purchase airtime
     */
    public function purchaseAirtime($network, $phoneNumber, $amount)
    {
        return $this->makeRequest('POST', '/topup', [
            'network' => $network,
            'phone' => $phoneNumber,
            'amount' => $amount
        ]);
    }

    // ==================== DATA SERVICES ====================

    /**
     * Get data plans
     */
    public function getDataPlans()
    {
        if (!$this->token) {
            $this->authenticate();
        }
        return $this->makeRequest('GET', "/website/app/{$this->token}/dataplan");
    }

    /**
     * Purchase data bundle
     */
    public function purchaseData($network, $phoneNumber, $planId)
    {
        return $this->makeRequest('POST', '/data', [
            'network' => $network,
            'phone' => $phoneNumber,
            'plan' => $planId
        ]);
    }

    // ==================== ELECTRICITY SERVICES ====================

    /**
     * Get electricity providers (DISCOs)
     */
    public function getElectricityProviders()
    {
        return $this->makeRequest('GET', '/website/app/disco');
    }

    /**
     * Validate meter number
     */
    public function validateMeter($meterNumber, $discoId)
    {
        return $this->makeRequest('GET', '/bill/bill-validation', [
            'meter_number' => $meterNumber,
            'disco' => $discoId
        ]);
    }

    /**
     * Purchase electricity
     */
    public function purchaseElectricity($discoId, $meterNumber, $amount)
    {
        return $this->makeRequest('POST', '/bill', [
            'disco' => $discoId,
            'meter_number' => $meterNumber,
            'amount' => $amount
        ]);
    }

    // ==================== CABLE TV SERVICES ====================

    /**
     * Get cable providers and plans
     */
    public function getCablePlans()
    {
        return $this->makeRequest('GET', '/website/app/cableplan');
    }

    /**
     * Validate decoder/smart card number
     */
    public function validateDecoder($iucNumber, $cableId)
    {
        return $this->makeRequest('GET', '/cable/cable-validation', [
            'iuc' => $iucNumber,
            'cable' => $cableId
        ]);
    }

    /**
     * Purchase cable subscription
     */
    public function purchaseCable($cableId, $smartCardNumber, $planId)
    {
        return $this->makeRequest('POST', '/cable', [
            'cablename' => $cableId,
            'smart_card_number' => $smartCardNumber,
            'cableplan' => $planId
        ]);
    }

    // ==================== EDUCATION SERVICES ====================

    /**
     * Get education providers
     */
    public function getEducationProviders()
    {
        return $this->makeRequest('GET', '/website/app/exam');
    }

    /**
     * Purchase education PIN
     */
    public function purchaseEducationPin($examId, $quantity = 1)
    {
        return $this->makeRequest('POST', '/exam', [
            'exam_name' => $examId,
            'quantity' => $quantity
        ]);
    }

    // ==================== BULK SMS SERVICES ====================

    /**
     * Send bulk SMS
     */
    public function sendBulkSms($sender, $message, $recipients)
    {
        return $this->makeRequest('POST', '/bulksms', [
            'sender' => $sender,
            'message' => $message,
            'recipients' => $recipients
        ]);
    }

    // ==================== AIRTIME TO CASH SERVICES ====================

    /**
     * Get supported numbers for airtime to cash
     */
    public function getAirtimeToCashNumbers()
    {
        return $this->makeRequest('GET', '/airtimecash/number');
    }

    /**
     * Convert airtime to cash
     */
    public function convertAirtimeToCash($network, $phoneNumber, $amount)
    {
        return $this->makeRequest('POST', '/cash', [
            'network' => $network,
            'phone' => $phoneNumber,
            'amount' => $amount
        ]);
    }

    // ==================== RECHARGE CARD SERVICES ====================

    /**
     * Get recharge card plans
     */
    public function getRechargeCardPlans()
    {
        if (!$this->token) {
            $this->authenticate();
        }
        return $this->makeRequest('GET', "/website/app/{$this->token}/recharge_card_pan");
    }

    /**
     * Print recharge cards
     */
    public function printRechargeCards($network, $planId, $quantity)
    {
        return $this->makeRequest('POST', '/recharge_card', [
            'network' => $network,
            'plan' => $planId,
            'quantity' => $quantity
        ]);
    }

    // ==================== DATA CARD SERVICES ====================

    /**
     * Get data card plans
     */
    public function getDataCardPlans()
    {
        if (!$this->token) {
            $this->authenticate();
        }
        return $this->makeRequest('GET', "/website/app/{$this->token}/data_card_pan");
    }

    /**
     * Print data cards
     */
    public function printDataCards($network, $planId, $quantity)
    {
        return $this->makeRequest('POST', '/data_card', [
            'network' => $network,
            'plan' => $planId,
            'quantity' => $quantity
        ]);
    }

    // ==================== ACCOUNT & TRANSACTION SERVICES ====================

    /**
     * Get account balance and details
     */
    public function getAccountDetails()
    {
        if (!$this->token) {
            $this->authenticate();
        }
        return $this->makeRequest('GET', "/account/my-account/{$this->token}");
    }

    /**
     * Get transaction history
     */
    public function getTransactionHistory($service = 'all')
    {
        if (!$this->token) {
            $this->authenticate();
        }

        $endpoints = [
            'all' => "/system/all/history/records/{$this->token}/secure",
            'data' => "/data/trans/{$this->token}/secure",
            'airtime' => "/airtime/trans/{$this->token}/secure",
            'cable' => "/cable/trans/{$this->token}/secure",
            'bill' => "/bill/trans/{$this->token}/secure",
            'bulksms' => "/bulksms/trans/{$this->token}/secure",
            'airtimecash' => "/airtimecash/trans/{$this->token}/secure",
            'education' => "/resultchecker/trans/{$this->token}/secure"
        ];

        $endpoint = $endpoints[$service] ?? $endpoints['all'];
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Check system configuration
     */
    public function getSystemConfig()
    {
        return $this->makeRequest('GET', '/website/app/setting');
    }

    /**
     * Check service status
     */
    public function checkServiceStatus($service)
    {
        $endpoints = [
            'cable' => '/website/app/cable/lock',
            'bill' => '/website/app/bill/list'
        ];

        if (isset($endpoints[$service])) {
            return $this->makeRequest('GET', $endpoints[$service]);
        }

        return ['status' => 'unknown', 'message' => 'Service status endpoint not available'];
    }
}