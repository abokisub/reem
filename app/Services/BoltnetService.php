<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BoltnetService
{
    private $baseUrl = 'https://boltnet.com.ng/api';
    private $apiKey;

    public function __construct()
    {
        // Retrieve API key from other_api table
        $apiConfig = DB::table('other_api')->first();
        $this->apiKey = $apiConfig->boltnet ?? null;
    }

    /**
     * Get authorization header
     */
    private function getHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Purchase data bundle
     * 
     * @param int $network Network ID (1=MTN, 2=AIRTEL, 3=GLO, 4=9MOBILE)
     * @param string $mobileNumber Phone number (10-13 digits)
     * @param int $plan Data plan ID
     * @param bool $portedNumber Whether number is ported
     */
    public function purchaseData($network, $mobileNumber, $plan, $portedNumber = true)
    {
        try {
            $payload = [
                'network' => $network,
                'mobile_number' => $mobileNumber,
                'plan' => $plan,
                'Ported_number' => $portedNumber,
            ];

            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/data/', $payload);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Data purchase failed',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Boltnet Data Purchase Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Purchase airtime
     * 
     * @param int $network Network ID (1=MTN, 2=AIRTEL, 3=GLO, 4=9MOBILE)
     * @param int $amount Airtime amount in Naira
     * @param string $mobileNumber Phone number (10-13 digits)
     * @param bool $portedNumber Whether number is ported
     * @param string $airtimeType Type of airtime (default: VTU)
     */
    public function purchaseAirtime($network, $amount, $mobileNumber, $portedNumber = true, $airtimeType = 'VTU')
    {
        try {
            $payload = [
                'network' => $network,
                'amount' => $amount,
                'mobile_number' => $mobileNumber,
                'Ported_number' => $portedNumber,
                'airtime_type' => $airtimeType,
            ];

            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/topup/', $payload);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Airtime purchase failed',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Boltnet Airtime Purchase Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Map Boltnet response to internal status
     * 
     * @param array $response Boltnet API response
     * @return string|null Internal status (success, fail, process)
     */
    public static function mapResponseStatus($response)
    {
        if (empty($response)) {
            return null;
        }

        // Check Status field (case-insensitive)
        $status = isset($response['Status']) ? strtolower($response['Status']) : null;

        switch ($status) {
            case 'successful':
            case 'success':
                return 'success';
            case 'failed':
            case 'fail':
                return 'fail';
            case 'pending':
            case 'processing':
                return 'process';
            default:
                return null;
        }
    }

    /**
     * Get base URL
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }
}
