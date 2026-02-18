<?php

namespace App\Services\Banking;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Banking Service - PalmPay Gateway
 * 
 * This service will be replaced with PalmPay integration.
 * For now, it's a placeholder to prevent errors during cleanup.
 */
class BankingService
{
    /**
     * Get list of supported banks from the Unified Database.
     * TODO: Integrate with PalmPay bank list API
     */
    public function getSupportedBanks()
    {
        return DB::table('unified_banks')
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Verify an account number.
     */
    public function verifyAccount(string $accountNumber, string $bankCode): array
    {
        try {
            $verificationService = new \App\Services\PalmPay\AccountVerificationService();
            $result = $verificationService->verifyAccount($accountNumber, $bankCode);

            if ($result['success']) {
                return [
                    'status' => 'success',
                    'data' => [
                        'account_name' => $result['account_name'],
                        'account_number' => $result['account_number'],
                        'bank_code' => $result['bank_code']
                    ]
                ];
            }

            return [
                'status' => 'fail',
                'message' => $result['message'] ?? 'Verification failed'
            ];
        } catch (\Exception $e) {
            Log::error("BankingService VerifyAccount Error: " . $e->getMessage());
            return [
                'status' => 'fail',
                'message' => 'Service error during verification'
            ];
        }
    }

    /**
     * Initiate a transfer.
     * TODO: Implement PalmPay transfer API
     */
    public function transfer(array $details): array
    {
        Log::warning("BankingService: Transfer called but PalmPay integration not yet implemented");

        return [
            'status' => 'pending',
            'message' => 'PalmPay integration in progress. Transfers temporarily unavailable.'
        ];
    }

    /**
     * Get wallet balance
     * TODO: Implement PalmPay balance query
     */
    public function getBalance(): array
    {
        return [
            'status' => 'pending',
            'balance' => 0,
            'message' => 'PalmPay integration in progress'
        ];
    }
    /**
     * Sync banks from a provider (e.g., Paystack, Monnify, PalmPay)
     * TODO: Implement actual sync logic for each provider
     */
    public function syncBanksFromProvider(string $provider): int
    {
        Log::warning("BankingService: syncBanksFromProvider called for $provider but not yet implemented");

        // Return 0 as no banks were synced in this placeholder
        return 0;
    }
}