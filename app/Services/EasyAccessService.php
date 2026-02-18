<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EasyAccessService
{
    private $baseUrl;
    private $token;

    public function __construct()
    {
        // Determine environment (test or live)
        $environment = env('EASYACCESS_ENV', 'test');

        $this->baseUrl = $environment === 'live'
            ? 'https://easyaccess.com.ng/api/live/v1/'
            : 'https://easyaccess.com.ng/api/test/v1/';

        // Retrieve token from other_api table
        $apiConfig = DB::table('other_api')->first();
        $this->token = $apiConfig->easy_access ?? null;
    }

    /**
     * Get authorization headers
     */
    private function getHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Cache-Control' => 'no-cache',
        ];
    }

    /**
     * Get wallet balance
     */
    public function getBalance()
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . 'wallet-balance');

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
            Log::error('Easy Access Balance Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get plans for a product type
     * 
     * @param string $productType (mtn_cg, glo_cg, dstv, gotv, waec, etc.)
     */
    public function getPlans($productType)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get($this->baseUrl . 'get-plans', [
                    'product_type' => $productType
                ]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Failed to retrieve plans',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Easy Access Plans Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Purchase data
     * 
     * @param int $network 1=MTN, 2=GLO, 3=AIRTEL, 4=9MOBILE
     * @param int $dataplan Plan ID
     * @param string $mobileno Phone number
     * @param string|null $clientReference Your transaction reference
     * @param int|null $maxAmountPayable Maximum amount willing to pay
     */
    public function purchaseData($network, $dataplan, $mobileno, $clientReference = null, $maxAmountPayable = null)
    {
        try {
            $payload = [
                'network' => $network,
                'dataplan' => $dataplan,
                'mobileno' => $mobileno,
            ];

            if ($clientReference) {
                $payload['client_reference'] = $clientReference;
            }

            if ($maxAmountPayable) {
                $payload['max_amount_payable'] = $maxAmountPayable;
            }

            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . 'purchase-data', $payload);

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
            Log::error('Easy Access Data Purchase Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify TV IUC/Smart Card
     * 
     * @param int $company 1=DSTV, 2=GOTV, 3=STARTIMES, 4=SHOWMAX
     * @param string $iucno IUC/Smart Card Number
     */
    public function verifyTV($company, $iucno)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . 'verify-tv', [
                    'company' => $company,
                    'iucno' => $iucno
                ]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'TV verification failed',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Easy Access TV Verify Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Pay TV subscription
     * 
     * @param int $company 1=DSTV, 2=GOTV, 3=STARTIMES, 4=SHOWMAX
     * @param int $package Plan ID
     * @param string $iucno IUC/Smart Card Number
     * @param int|null $maxAmountPayable Maximum amount willing to pay
     */
    public function payTV($company, $package, $iucno, $maxAmountPayable = null)
    {
        try {
            $payload = [
                'company' => $company,
                'package' => $package,
                'iucno' => $iucno,
            ];

            if ($maxAmountPayable) {
                $payload['max_amount_payable'] = $maxAmountPayable;
            }

            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . 'pay-tv', $payload);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'TV payment failed',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Easy Access TV Payment Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify electricity meter
     * 
     * @param int $company Disco ID
     * @param int $metertype 1=PREPAID, 2=POSTPAID
     * @param string $meterno Meter Number
     * @param int $amount Amount (min 1000)
     */
    public function verifyElectricity($company, $metertype, $meterno, $amount)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . 'verify-electricity', [
                    'company' => $company,
                    'metertype' => $metertype,
                    'meterno' => $meterno,
                    'amount' => $amount
                ]);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Meter verification failed',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Easy Access Electricity Verify Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Pay electricity bill
     * 
     * @param int $company Disco ID
     * @param int $metertype 1=PREPAID, 2=POSTPAID
     * @param string $meterno Meter Number
     * @param int $amount Amount (min 1000)
     * @param int|null $maxAmountPayable Maximum amount willing to pay
     */
    public function payElectricity($company, $metertype, $meterno, $amount, $maxAmountPayable = null)
    {
        try {
            $payload = [
                'company' => $company,
                'metertype' => $metertype,
                'meterno' => $meterno,
                'amount' => $amount,
            ];

            if ($maxAmountPayable) {
                $payload['max_amount_payable'] = $maxAmountPayable;
            }

            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . 'pay-electricity', $payload);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Electricity payment failed',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Easy Access Electricity Payment Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Purchase exam pins
     * 
     * @param int $examBoard 1=WAEC, 2=NECO, 3=NABTEB, 4=NBAIS
     * @param int $noOfPins Number of pins (1-50)
     * @param int|null $maxAmountPayable Maximum amount willing to pay
     */
    public function purchaseExamPins($examBoard, $noOfPins, $maxAmountPayable = null)
    {
        try {
            $payload = [
                'exam_board' => $examBoard,
                'no_of_pins' => $noOfPins,
            ];

            if ($maxAmountPayable) {
                $payload['max_amount_payable'] = $maxAmountPayable;
            }

            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . 'exam-pins', $payload);

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json()
                ];
            }

            return [
                'status' => 'error',
                'message' => 'Exam pins purchase failed',
                'data' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Easy Access Exam Pins Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Query transaction status
     * 
     * @param string $reference Transaction reference
     */
    public function queryTransaction($reference)
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->post($this->baseUrl . 'query-transactions', [
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
            Log::error('Easy Access Query Transaction Error: ' . $e->getMessage());
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Map Easy Access response to internal status
     * 
     * @param array $response Easy Access API response
     * @return string|null Internal status (success, fail, process)
     */
    public static function mapResponseStatus($response)
    {
        if (empty($response)) {
            return null;
        }

        // Check HTTP code
        $code = $response['code'] ?? null;

        // Check status value (case-insensitive)
        $status = isset($response['status']) ? strtolower($response['status']) : null;

        // Success: code 200/201 OR status contains 'success'
        if (in_array($code, [200, 201]) || in_array($status, ['success', 'successful'])) {
            return 'success';
        }

        // Failed: code 400/401 OR status contains 'failed'
        if (in_array($code, [400, 401]) || $status === 'failed') {
            return 'fail';
        }

        return null;
    }

    /**
     * Get current environment
     */
    public function getEnvironment()
    {
        return env('EASYACCESS_ENV', 'test');
    }

    /**
     * Get base URL
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }
}
