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
     * Initiate a transfer using PalmPay
     */
    public function transfer(array $details): array
    {
        try {
            // Resolve TransferService from container with dependencies
            $transferService = app(\App\Services\PalmPay\TransferService::class);
            
            // Get company ID from details or use default
            $companyId = $details['company_id'] ?? 1;
            
            // Prepare transfer data
            $transferData = [
                'amount' => $details['amount'],
                'account_number' => $details['account_number'],
                'account_name' => $details['account_name'] ?? null,
                'bank_code' => $details['bank_code'],
                'bank_name' => $details['bank_name'] ?? null,
                'narration' => $details['narration'] ?? 'Bank Transfer',
                'reference' => $details['reference'] ?? null,
                'metadata' => $details['metadata'] ?? null,
                'balance_already_deducted' => $details['balance_already_deducted'] ?? false,  // Forward context flag
                'transaction_reference' => $details['transaction_reference'] ?? null  // Forward transaction reference
            ];
            
            // Initiate transfer
            $transaction = $transferService->initiateTransfer($companyId, $transferData);
            
            // Check if transaction failed
            if ($transaction->status === 'failed') {
                return [
                    'status' => 'fail',
                    'message' => $transaction->error_message ?? 'Transfer failed'
                ];
            }
            
            return [
                'status' => $transaction->status,
                'message' => 'Transfer initiated successfully',
                'transaction_id' => $transaction->transaction_id,
                'reference' => $transaction->reference,
                'bank_name' => $transaction->recipient_bank_name
            ];
            
        } catch (\Exception $e) {
            Log::error("BankingService Transfer Error: " . $e->getMessage());
            
            return [
                'status' => 'fail',
                'message' => $e->getMessage()
            ];
        }
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