<?php

namespace App\Services\PalmPay;

use App\Services\PalmPay\PalmPayClient;
use Illuminate\Support\Facades\Log;

/**
 * PalmPay Account Verification Service
 * 
 * Verifies Nigerian bank account details
 */
class AccountVerificationService
{
    private PalmPayClient $client;

    public function __construct()
    {
        $this->client = new PalmPayClient();
    }

    /**
     * Verify a bank account
     * 
     * @param string $accountNumber
     * @param string $bankCode
     * @return array
     */
    public function verifyAccount(string $accountNumber, string $bankCode): array
    {
        try {
            Log::info('Verifying Bank Account', [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode
            ]);

            // Call PalmPay API
            $response = $this->client->post('/api/v2/payment/merchant/payout/queryBankAccount', [
                'bankCode' => $bankCode,
                'bankAccNo' => $accountNumber,
            ]);

            $accountName = $response['data']['accountName'] ?? null;

            if (!$accountName) {
                throw new \Exception('Account verification failed: ' . ($response['respMsg'] ?? 'Unknown error'));
            }

            Log::info('Account Verified Successfully', [
                'account_number' => $accountNumber,
                'account_name' => $accountName
            ]);

            return [
                'success' => true,
                'account_number' => $accountNumber,
                'account_name' => $accountName,
                'bank_code' => $bankCode,
            ];

        } catch (\Exception $e) {
            Log::error('Account Verification Failed', [
                'account_number' => $accountNumber,
                'bank_code' => $bankCode,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get list of supported banks
     * 
     * @return array
     */
    public function getBanks(): array
    {
        try {
            $response = $this->client->post('/api/v2/general/merchant/queryBankList', [
                'businessType' => 0
            ]);

            // The API might return a list directly in 'data' or wrapped
            // Based on typical PalmPay response: { respCode: "00000000", data: [ ... ] }
            $banks = $response['data'] ?? [];

            // Ensure we return an array
            return is_array($banks) ? $banks : [];

        } catch (\Exception $e) {
            Log::error('Failed to Fetch Banks', [
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Verify a PalmPay account
     * 
     * @param string $accountNumber PalmPay account number or phone number
     * @return array
     */
    public function verifyPalmPayAccount(string $accountNumber): array
    {
        try {
            Log::info('Verifying PalmPay Account', [
                'account_number' => $accountNumber
            ]);

            // Call PalmPay API
            // Path: /api/v2/payment/merchant/payout/queryPalmPayAccount
            $response = $this->client->post('/api/v2/payment/merchant/payout/queryPalmPayAccount', [
                'accountNo' => $accountNumber,
            ]);

            $accountName = $response['data']['accountName'] ?? null;
            $available = $response['data']['available'] ?? false;

            if (!$accountName) {
                throw new \Exception('PalmPay account verification failed: ' . ($response['respMsg'] ?? 'Unknown error'));
            }

            Log::info('PalmPay Account Verified Successfully', [
                'account_number' => $accountNumber,
                'account_name' => $accountName,
                'available' => $available
            ]);

            return [
                'success' => true,
                'account_number' => $accountNumber,
                'account_name' => $accountName,
                'available' => $available,
            ];

        } catch (\Exception $e) {
            Log::error('PalmPay Account Verification Failed', [
                'account_number' => $accountNumber,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}