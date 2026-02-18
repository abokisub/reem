<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmePlugService
{
    private $baseUrl = 'https://smeplug.ng/api/v1';
    private $bearerToken;

    public function __construct()
    {
        // Retrieve Bearer Token from other_api table
        $apiConfig = DB::table('other_api')->first();
        $this->bearerToken = $apiConfig->smeplug ?? null;
    }

    /**
     * Get SME Plug wallet balance
     */
    public function getBalance()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bearerToken,
            ])->get($this->baseUrl . '/account/balance');

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
            Log::error('SME Plug Balance Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get available networks
     */
    public function getNetworks()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bearerToken,
            ])->get($this->baseUrl . '/networks');

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Failed to retrieve networks',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('SME Plug Networks Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get data plans for all networks
     */
    public function getDataPlans()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bearerToken,
            ])->get($this->baseUrl . '/data/plans');

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Failed to retrieve data plans',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('SME Plug Data Plans Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Purchase data plan
     * 
     * @param int $networkId Network ID (1=MTN, 2=Airtel, 3=9Mobile, 4=Glo)
     * @param string $planId Plan ID from SME Plug
     * @param string $phone Beneficiary phone number
     * @param string|null $customerReference Optional customer reference
     */
    public function purchaseData($networkId, $planId, $phone, $customerReference = null)
    {
        try {
            $payload = [
                'network_id' => $networkId,
                'plan_id' => $planId,
                'phone' => $phone,
            ];

            if ($customerReference) {
                $payload['customer_reference'] = $customerReference;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bearerToken,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/data/purchase', $payload);

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
            Log::error('SME Plug Data Purchase Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Purchase airtime
     * 
     * @param int $networkId Network ID (1=MTN, 2=Airtel, 3=9Mobile, 4=Glo)
     * @param string $phone Beneficiary phone number
     * @param float $amount Amount to purchase
     * @param string|null $customerReference Optional customer reference
     */
    public function purchaseAirtime($networkId, $phone, $amount, $customerReference = null)
    {
        try {
            $payload = [
                'network_id' => $networkId,
                'phone' => $phone,
                'amount' => $amount,
            ];

            if ($customerReference) {
                $payload['customer_reference'] = $customerReference;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bearerToken,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . '/airtime/purchase', $payload);

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
            Log::error('SME Plug Airtime Purchase Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Requery transaction status
     * 
     * @param string $reference SME Plug reference or customer_reference
     */
    public function requeryTransaction($reference)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bearerToken,
            ])->get($this->baseUrl . '/transactions/' . $reference);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Transaction requery failed',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('SME Plug Transaction Requery Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get registered devices (for SIM-based transactions)
     */
    public function getDevices()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->bearerToken,
            ])->get($this->baseUrl . '/devices');

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Failed to retrieve devices',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('SME Plug Devices Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}
