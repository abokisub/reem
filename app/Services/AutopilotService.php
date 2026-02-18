<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AutopilotService
{
    private $baseUrl;
    private $apiKey;

    public function __construct()
    {
        // Determine environment (test or live)
        $environment = env('AUTOPILOT_ENV', 'test');

        $this->baseUrl = $environment === 'live'
            ? 'https://autopilotng.com/api/live'
            : 'https://autopilotng.com/api/test';

        // Retrieve API key from other_api table
        $apiConfig = DB::table('other_api')->first();
        $this->apiKey = $apiConfig->autopilot ?? null;
    }

    /**
     * Get authorization headers
     */
    private function getHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Get wallet balance
     * 
     * @param string $email User email
     */
    public function getBalance($email)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/v1/load/wallet-balance', [
                    'email' => $email
                ]);

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
            Log::error('Autopilot Balance Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all networks
     */
    public function getNetworks()
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/v1/load/networks', [
                    'networks' => 'all'
                ]);

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
            Log::error('Autopilot Networks Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get data types for a network
     * 
     * @param int $networkId Network ID (1=MTN, 2=AIRTEL, 3=GLO, 4=9MOBILE)
     */
    public function getDataTypes($networkId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/v1/load/data-types', [
                    'networkId' => (string) $networkId
                ]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Failed to retrieve data types',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Autopilot Data Types Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get data plans
     * 
     * @param int $networkId Network ID
     * @param string $dataType Data type (SME, CORPORATE GIFTING, DIRECT GIFTING)
     */
    public function getDataPlans($networkId, $dataType)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/v1/load/data', [
                    'networkId' => (string) $networkId,
                    'dataType' => $dataType
                ]);

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
            Log::error('Autopilot Data Plans Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Purchase data
     * 
     * @param int $networkId Network ID
     * @param string $dataType Data type
     * @param string $planId Plan ID
     * @param string $phone Phone number
     * @param string $reference Unique reference
     */
    public function purchaseData($networkId, $dataType, $planId, $phone, $reference)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/v1/data', [
                    'networkId' => (string) $networkId,
                    'dataType' => $dataType,
                    'planId' => $planId,
                    'phone' => $phone,
                    'reference' => $reference
                ]);

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
            Log::error('Autopilot Data Purchase Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get airtime types for a network
     * 
     * @param int $networkId Network ID
     */
    public function getAirtimeTypes($networkId)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/v1/load/airtime-types', [
                    'networkId' => (string) $networkId
                ]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Failed to retrieve airtime types',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Autopilot Airtime Types Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Purchase airtime
     * 
     * @param int $networkId Network ID
     * @param string $airtimeType Airtime type (VTU, AWUF, SNS)
     * @param int $amount Amount
     * @param string $phone Phone number
     * @param string $reference Unique reference
     * @param int|null $quantity Quantity (for SNS)
     */
    public function purchaseAirtime($networkId, $airtimeType, $amount, $phone, $reference, $quantity = null)
    {
        try {
            $payload = [
                'networkId' => (string) $networkId,
                'airtimeType' => $airtimeType,
                'amount' => (string) $amount,
                'phone' => $phone,
                'reference' => $reference
            ];

            if ($quantity !== null) {
                $payload['quantity'] = (string) $quantity;
            }

            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/v1/airtime', $payload);

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
            Log::error('Autopilot Airtime Purchase Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get cable types
     */
    public function getCableTypes()
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/v1/load/cable-types', [
                    'cables' => 'all'
                ]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Failed to retrieve cable types',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Autopilot Cable Types Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get cable packages
     * 
     * @param string $cableType Cable type (DSTV, GOTV, STARTIMES, SHOWMAX)
     */
    public function getCablePackages($cableType)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/v1/load/cable-packages', [
                    'cableType' => $cableType
                ]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Failed to retrieve cable packages',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Autopilot Cable Packages Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate smart card number
     * 
     * @param string $cableType Cable type
     * @param string $smartCardNo Smart card number
     */
    public function validateSmartCard($cableType, $smartCardNo)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/v1/validate/smartcard-no', [
                    'cableType' => $cableType,
                    'smartCardNo' => $smartCardNo
                ]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Smart card validation failed',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Autopilot Smart Card Validation Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Subscribe cable TV
     * 
     * @param string $cableType Cable type
     * @param string $planId Plan ID
     * @param string $paymentType Payment type (TOP_UP or FULL_PAYMENT)
     * @param string $reference Unique reference
     * @param int|null $amount Amount (for TOP_UP)
     * @param string|null $customerName Customer name
     * @param string|null $smartCardNo Smart card number
     * @param string|null $phoneNo Phone number (for SHOWMAX)
     */
    public function subscribeCable($cableType, $planId, $paymentType, $reference, $amount = null, $customerName = null, $smartCardNo = null, $phoneNo = null)
    {
        try {
            $payload = [
                'cableType' => $cableType,
                'planId' => $planId,
                'paymentTypes' => $paymentType,
                'reference' => $reference
            ];

            if ($amount !== null) {
                $payload['amount'] = (string) $amount;
            }

            if ($customerName !== null) {
                $payload['customerName'] = $customerName;
            }

            if ($smartCardNo !== null) {
                $payload['smartCardNo'] = $smartCardNo;
            }

            if ($phoneNo !== null) {
                $payload['phoneNo'] = $phoneNo;
            }

            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/v1/cable', $payload);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Cable subscription failed',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Autopilot Cable Subscription Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Query transaction status
     * 
     * @param string $product Product type (data, airtime, airtime-to-cash)
     * @param string $reference Transaction reference
     */
    public function queryTransaction($product, $reference)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . '/v1/transaction/status/' . $product, [
                    'reference' => $reference
                ]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Transaction query failed',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Autopilot Query Transaction Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Map Autopilot response to internal status
     * 
     * @param array $response Autopilot API response
     * @return string|null Internal status (success, fail, process)
     */
    public static function mapResponseStatus($response)
    {
        if (empty($response)) {
            return null;
        }

        // Check status field
        $status = $response['status'] ?? null;
        $code = $response['code'] ?? null;

        // Success: status=true AND code=200 or 201
        if ($status === true && in_array($code, [200, 201])) {
            return 'success';
        }

        // Failed: status=false OR code in error range
        if ($status === false || in_array($code, [401, 409, 424, 429]) || $code >= 500) {
            return 'fail';
        }

        return null;
    }

    /**
     * Get current environment
     */
    public function getEnvironment()
    {
        return env('AUTOPILOT_ENV', 'test');
    }

    /**
     * Get base URL
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }
}
