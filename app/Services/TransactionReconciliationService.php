<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionStatusLog;
use App\Services\PalmPay\TransferService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionReconciliationService
{
    private TransferService $transferService;
    
    public function __construct(TransferService $transferService)
    {
        $this->transferService = $transferService;
    }
    
    /**
     * Reconcile all pending/processing transactions with provider
     */
    public function reconcileAll(): array
    {
        $results = [
            'checked' => 0,
            'confirmed_success' => 0,
            'confirmed_failure' => 0,
            'timeout' => 0,
            'errors' => []
        ];
        
        // Find transactions needing reconciliation
        $transactions = Transaction::where(function($query) {
            $query->where('status', 'processing')
                  ->orWhere('status', 'pending');
        })
        ->where('reconciliation_status', 'pending')
        ->where('reconciliation_attempt_count', '<', 10) // Max 10 attempts
        ->orderBy('created_at', 'asc')
        ->limit(100) // Process in batches
        ->get();
        
        foreach ($transactions as $transaction) {
            $results['checked']++;
            
            try {
                $result = $this->reconcileTransaction($transaction);
                
                if ($result['status'] === 'success') {
                    $results['confirmed_success']++;
                } elseif ($result['status'] === 'failure') {
                    $results['confirmed_failure']++;
                } elseif ($result['status'] === 'timeout') {
                    $results['timeout']++;
                }
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ];
                Log::error('Reconciliation error', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        return $results;
    }
    
    /**
     * Reconcile single transaction with provider
     */
    public function reconcileTransaction(Transaction $transaction): array
    {
        // Increment attempt count
        $transaction->increment('reconciliation_attempt_count');
        $transaction->update(['last_reconciliation_at' => now()]);
        
        // Query provider status endpoint
        $providerStatus = $this->queryProviderStatus($transaction);
        
        if ($providerStatus['status'] === 'SUCCESS') {
            return $this->handleProviderSuccess($transaction, $providerStatus);
        } elseif ($providerStatus['status'] === 'FAILED') {
            return $this->handleProviderFailure($transaction, $providerStatus);
        } elseif ($providerStatus['status'] === 'TIMEOUT' || $providerStatus['status'] === 'PENDING') {
            return $this->handleProviderTimeout($transaction);
        }
        
        return ['status' => 'unknown'];
    }
    
    /**
     * Query provider status endpoint
     */
    private function queryProviderStatus(Transaction $transaction): array
    {
        try {
            // Use provider_reference to query status
            if (!$transaction->provider_reference) {
                return ['status' => 'UNKNOWN', 'message' => 'No provider reference'];
            }
            
            // Query PalmPay status endpoint
            $response = $this->transferService->queryTransactionStatus(
                $transaction->provider_reference
            );
            
            return [
                'status' => $response['status'] ?? 'UNKNOWN',
                'message' => $response['message'] ?? '',
                'provider_data' => $response
            ];
        } catch (\Exception $e) {
            Log::error('Provider status query failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            
            return ['status' => 'TIMEOUT', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Handle provider confirmed success
     */
    private function handleProviderSuccess(Transaction $transaction, array $providerStatus): array
    {
        DB::beginTransaction();
        
        try {
            $oldStatus = $transaction->status;
            $oldSettlementStatus = $transaction->settlement_status;
            
            // Update transaction status
            $transaction->update([
                'status' => 'successful',
                'settlement_status' => 'settled',
                'reconciliation_status' => 'completed',
                'reconciliation_completed_at' => now()
            ]);
            
            // Log status change
            TransactionStatusLog::create([
                'transaction_id' => $transaction->id,
                'old_status' => $oldStatus,
                'new_status' => 'successful',
                'old_settlement_status' => $oldSettlementStatus,
                'new_settlement_status' => 'settled',
                'changed_by' => 'system_reconciliation',
                'reason' => 'Provider confirmed success',
                'metadata' => json_encode([
                    'provider_status' => $providerStatus['status'],
                    'provider_message' => $providerStatus['message'] ?? '',
                    'reconciliation_attempt' => $transaction->reconciliation_attempt_count
                ])
            ]);
            
            DB::commit();
            
            Log::info('Transaction reconciled to success', [
                'transaction_id' => $transaction->id,
                'transaction_ref' => $transaction->transaction_ref,
                'old_status' => $oldStatus,
                'new_status' => 'successful'
            ]);
            
            return ['status' => 'success', 'transaction' => $transaction];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Handle provider confirmed failure
     */
    private function handleProviderFailure(Transaction $transaction, array $providerStatus): array
    {
        DB::beginTransaction();
        
        try {
            $oldStatus = $transaction->status;
            $oldSettlementStatus = $transaction->settlement_status;
            
            // Trigger safe ledger reversal
            $this->reverseLedgerEntries($transaction);
            
            // Update transaction status
            $transaction->update([
                'status' => 'failed',
                'settlement_status' => 'not_applicable',
                'reconciliation_status' => 'completed',
                'reconciliation_completed_at' => now()
            ]);
            
            // Log status change
            TransactionStatusLog::create([
                'transaction_id' => $transaction->id,
                'old_status' => $oldStatus,
                'new_status' => 'failed',
                'old_settlement_status' => $oldSettlementStatus,
                'new_settlement_status' => 'not_applicable',
                'changed_by' => 'system_reconciliation',
                'reason' => 'Provider confirmed failure',
                'metadata' => json_encode([
                    'provider_status' => $providerStatus['status'],
                    'provider_message' => $providerStatus['message'] ?? '',
                    'reconciliation_attempt' => $transaction->reconciliation_attempt_count,
                    'ledger_reversed' => true
                ])
            ]);
            
            DB::commit();
            
            Log::info('Transaction reconciled to failure', [
                'transaction_id' => $transaction->id,
                'transaction_ref' => $transaction->transaction_ref,
                'old_status' => $oldStatus,
                'new_status' => 'failed'
            ]);
            
            return ['status' => 'failure', 'transaction' => $transaction];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Handle provider timeout/pending
     */
    private function handleProviderTimeout(Transaction $transaction): array
    {
        // Keep status as processing, will retry later
        Log::info('Transaction still pending at provider', [
            'transaction_id' => $transaction->id,
            'transaction_ref' => $transaction->transaction_ref,
            'attempt_count' => $transaction->reconciliation_attempt_count
        ]);
        
        return ['status' => 'timeout', 'transaction' => $transaction];
    }
    
    /**
     * Reverse ledger entries for failed transaction
     */
    private function reverseLedgerEntries(Transaction $transaction): void
    {
        // Find all ledger entries for this transaction
        $ledgerEntries = DB::table('ledger_entries')
            ->where('transaction_id', $transaction->id)
            ->get();
        
        foreach ($ledgerEntries as $entry) {
            // Create reversal entry
            DB::table('ledger_entries')->insert([
                'transaction_id' => $transaction->id,
                'company_id' => $entry->company_id,
                'customer_id' => $entry->customer_id,
                'entry_type' => $entry->entry_type === 'debit' ? 'credit' : 'debit',
                'amount' => $entry->amount,
                'balance_before' => null, // Will be calculated
                'balance_after' => null,  // Will be calculated
                'description' => 'Reversal: ' . $entry->description,
                'reference' => $transaction->transaction_ref . '_reversal',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        
        Log::info('Ledger entries reversed', [
            'transaction_id' => $transaction->id,
            'entries_reversed' => $ledgerEntries->count()
        ]);
    }
}
