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
                // Parse error message from PalmPay response
                $errorMsg = $this->parseErrorMessage($response);
                throw new \Exception($errorMsg);
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
                'error' => $e->getMessage(),
                'raw_response' => $response ?? null
            ]);

            // Clean up error message
            $errorMessage = $this->cleanErrorMessage($e->getMessage());

            return [
                'success' => false,
                'message' => $errorMessage,
                'raw_error' => $e->getMessage(), // Keep original for debugging
            ];
        }
    }

    /**
     * Parse error message from PalmPay response
     * 
     * @param array $response
     * @return string
     */
    private function parseErrorMessage(array $response): string
    {
        $respMsg = $response['respMsg'] ?? ($response['message'] ?? null);
        $respCode = $response['respCode'] ?? ($response['code'] ?? null);

        // If respMsg is empty, generic, or confusing
        if (empty($respMsg) || in_array(strtolower($respMsg), ['success', 'ok', 'failed'])) {
            // Try to determine error from response code
            if ($respCode) {
                return $this->getErrorMessageFromCode($respCode);
            }
            return 'Account not found or invalid';
        }

        return $respMsg;
    }

    /**
     * Get user-friendly error message from PalmPay error code
     * 
     * @param string $code
     * @return string
     */
    private function getErrorMessageFromCode(string $code): string
    {
        $errorCodes = [
            'OPEN_GW_000001' => 'Invalid request parameters',
            'OPEN_GW_000002' => 'Account not found',
            'OPEN_GW_000003' => 'Bank service temporarily unavailable',
            'OPEN_GW_000004' => 'Invalid bank code',
            'OPEN_GW_000005' => 'Account verification not supported for this bank',
            'OPEN_GW_000012' => 'IP not whitelisted - Please contact support to whitelist your server IP',
        ];

        return $errorCodes[$code] ?? "Account verification failed (Code: {$code})";
    }

    /**
     * Clean up confusing error messages
     * 
     * @param string $message
     * @return string
     */
    private function cleanErrorMessage(string $message): string
    {
        // Remove "Account verification failed: " prefix if it exists
        $message = preg_replace('/^Account verification failed:\s*/i', '', $message);
        
        // If message is just "success" or similar confusing terms
        if (in_array(strtolower(trim($message)), ['success', 'ok', 'failed', 'error'])) {
            return 'Account not found or bank does not support verification';
        }

        // If message contains "PalmPay Error:" extract the actual error
        if (preg_match('/PalmPay Error:\s*(.+?)(?:\s*\(Code:|$)/i', $message, $matches)) {
            $extractedMsg = trim($matches[1]);
            // Check if extracted message is also confusing
            if (!in_array(strtolower($extractedMsg), ['success', 'ok', 'failed'])) {
                return $extractedMsg;
            }
        }

        return $message;
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