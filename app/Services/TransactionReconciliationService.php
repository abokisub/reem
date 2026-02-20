<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\TransactionStatusLog;
use App\Services\PalmPay\VirtualAccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Transaction Reconciliation Service
 * 
 * Handles status reconciliation between internal transaction records and external payment providers.
 * Implements the canonical status source pattern where transactions.status is authoritative.
 * 
 * @package App\Services
 */
class TransactionReconciliationService
{
    /**
     * @var VirtualAccountService
     */
    protected $palmPayService;

    /**
     * Provider status to system status mapping
     * 
     * @var array
     */
    private const PROVIDER_STATUS_MAP = [
        'SUCCESS' => 'successful',
        'COMPLETED' => 'successful',
        'FAILED' => 'failed',
        'REJECTED' => 'failed',
        'PENDING' => 'pending',
        'INITIATED' => 'pending',
        'PROCESSING' => 'processing',
        'REVERSED' => 'reversed',
        'REFUNDED' => 'reversed',
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->palmPayService = new VirtualAccountService();
    }

    /**
     * Reconcile transaction from webhook data
     * 
     * Processes provider webhook and updates transaction atomically.
     * Uses DB transaction to ensure status and settlement_status are updated together.
     * Logs all changes to transaction_status_logs with source='webhook'.
     * 
     * @param array $webhookData Webhook payload containing provider_reference and status
     * @return bool Success/failure result
     */
    public function reconcileFromWebhook(array $webhookData): bool
    {
        $providerReference = $webhookData['provider_reference'] ?? null;
        $providerStatus = $webhookData['status'] ?? null;

        if (!$providerReference || !$providerStatus) {
            Log::warning('Webhook received with missing required fields', [
                'webhook_data' => $webhookData
            ]);
            return false;
        }

        // Find transaction by provider reference
        $transaction = Transaction::where('provider_reference', $providerReference)
            ->first();

        if (!$transaction) {
            Log::warning('Webhook received for unknown transaction', [
                'provider_reference' => $providerReference,
                'provider_status' => $providerStatus
            ]);
            return false;
        }

        // Map provider status to system status
        $newStatus = $this->mapProviderStatus($providerStatus);

        // Only update if status has changed
        if ($transaction->status !== $newStatus) {
            $this->updateTransactionStatus($transaction, $newStatus, [
                'source' => 'webhook',
                'provider_status' => $providerStatus,
                'webhook_data' => $webhookData,
            ]);
            return true;
        } else {
            Log::info('Webhook received but status unchanged', [
                'transaction_ref' => $transaction->transaction_ref,
                'current_status' => $transaction->status,
                'provider_status' => $providerStatus
            ]);
            return true;
        }
    }

    /**
     * Map provider status to system status
     * 
     * Converts external provider status codes to the 5-state system model:
     * pending, processing, successful, failed, reversed
     * 
     * @param string $providerStatus Provider's status code
     * @return string System status
     */
    public function mapProviderStatus(string $providerStatus): string
    {
        $normalizedStatus = strtoupper(trim($providerStatus));
        
        return self::PROVIDER_STATUS_MAP[$normalizedStatus] ?? 'failed';
    }

    /**
     * Update transaction status atomically
     * 
     * Updates transaction status and settlement_status within a database transaction.
     * Logs all status changes to TransactionStatusLog for audit trail.
     * Updates reconciliation_status and reconciled_at when status reaches final state.
     * 
     * @param Transaction $transaction Transaction to update
     * @param string $newStatus New status value
     * @param array $metadata Additional metadata about the status change
     * @return bool Success indicator
     */
    private function updateTransactionStatus(
        Transaction $transaction,
        string $newStatus,
        array $metadata = []
    ): bool {
        $oldStatus = $transaction->status;
        $source = $metadata['source'] ?? 'webhook';

        try {
            DB::transaction(function () use ($transaction, $newStatus, $oldStatus, $metadata, $source) {
                // Determine settlement_status based on new status
                $settlementStatus = $this->determineSettlementStatus($newStatus, $transaction->transaction_type);

                // Prepare update data
                $updateData = [
                    'status' => $newStatus,
                    'settlement_status' => $settlementStatus,
                    'processed_at' => now(),
                ];

                // Update reconciliation_status if status reaches final state
                if (in_array($newStatus, ['successful', 'failed', 'reversed'])) {
                    $updateData['reconciliation_status'] = 'reconciled';
                    $updateData['reconciled_at'] = now();
                }

                // Update transaction
                $transaction->update($updateData);

                // Log status change to audit trail
                TransactionStatusLog::create([
                    'transaction_id' => $transaction->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'source' => $source,
                    'metadata' => array_merge($metadata, [
                        'settlement_status' => $settlementStatus,
                    ]),
                    'changed_at' => now(),
                ]);
            });

            Log::info('Transaction status reconciled', [
                'transaction_ref' => $transaction->transaction_ref,
                'session_id' => $transaction->session_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'settlement_status' => $transaction->settlement_status,
                'source' => $source,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update transaction status', [
                'transaction_ref' => $transaction->transaction_ref,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Determine settlement status based on transaction status
     * 
     * Implements business rules for settlement_status calculation:
     * - Internal types (fee_charge, kyc_charge, manual_adjustment): not_applicable
     * - Failed/reversed transactions: not_applicable
     * - Successful transactions: settled
     * - Pending/processing transactions: unsettled
     * 
     * @param string $status Transaction status
     * @param string|null $transactionType Transaction type
     * @return string Settlement status
     */
    public function determineSettlementStatus(string $status, ?string $transactionType = null): string
    {
        // Internal accounting entries don't require settlement
        if ($transactionType && in_array($transactionType, ['fee_charge', 'kyc_charge', 'manual_adjustment'])) {
            return 'not_applicable';
        }

        // Failed/reversed transactions don't settle
        if (in_array($status, ['failed', 'reversed'])) {
            return 'not_applicable';
        }

        // Successful transactions are settled
        if ($status === 'successful') {
            return 'settled';
        }

        // Default to unsettled for pending/processing
        return 'unsettled';
    }

    /**
     * Run scheduled reconciliation for stale transactions
     * 
     * Queries provider status for transactions stuck in 'processing' or 'pending' state
     * with reconciliation_status = 'pending'. This catches transactions where webhooks
     * were missed or failed.
     * 
     * For each transaction:
     * - Queries provider for current status
     * - If provider confirms SUCCESS: updates to 'successful' and 'settled'
     * - If provider confirms FAILURE: updates to 'failed' and 'not_applicable'
     * - If provider returns TIMEOUT: keeps status as 'processing'
     * 
     * @return int Count of successfully reconciled transactions
     */
    public function runScheduledReconciliation(): int
    {
        Log::info('Starting scheduled transaction reconciliation');

        // Find transactions where status IN ('processing', 'pending') AND reconciliation_status = 'pending'
        $staleTransactions = Transaction::whereIn('status', ['processing', 'pending'])
            ->where('reconciliation_status', 'pending')
            ->whereNotNull('provider_reference')
            ->get();

        if ($staleTransactions->isEmpty()) {
            Log::info('No stale transactions found for reconciliation');
            return 0;
        }

        Log::info('Found stale transactions for reconciliation', [
            'count' => $staleTransactions->count()
        ]);

        $reconciledCount = 0;
        $failedCount = 0;
        $timeoutCount = 0;

        foreach ($staleTransactions as $transaction) {
            try {
                $providerStatus = $this->queryProviderStatus($transaction->provider_reference);
                
                if ($providerStatus === null) {
                    // Provider timeout - keep status as 'processing'
                    $timeoutCount++;
                    Log::warning('Provider timeout for transaction', [
                        'transaction_ref' => $transaction->transaction_ref,
                        'provider_reference' => $transaction->provider_reference
                    ]);
                    continue;
                }

                $newStatus = $this->mapProviderStatus($providerStatus);

                // Only update if status has changed
                if ($transaction->status !== $newStatus) {
                    // Determine settlement_status based on new status
                    $settlementStatus = $this->determineSettlementStatus($newStatus, $transaction->transaction_type);

                    // Use DB transaction for atomicity
                    DB::transaction(function () use ($transaction, $newStatus, $settlementStatus, $providerStatus) {
                        $oldStatus = $transaction->status;

                        // Update transaction status and settlement_status
                        $transaction->update([
                            'status' => $newStatus,
                            'settlement_status' => $settlementStatus,
                            'reconciliation_status' => 'reconciled',
                            'reconciled_at' => now(),
                            'processed_at' => now(),
                        ]);

                        // Log status change to audit trail
                        TransactionStatusLog::create([
                            'transaction_id' => $transaction->id,
                            'old_status' => $oldStatus,
                            'new_status' => $newStatus,
                            'source' => 'scheduled_reconciliation',
                            'metadata' => [
                                'provider_status' => $providerStatus,
                                'settlement_status' => $settlementStatus,
                                'stale_duration_hours' => now()->diffInHours($transaction->updated_at),
                            ],
                            'changed_at' => now(),
                        ]);
                    });

                    $reconciledCount++;

                    Log::info('Transaction reconciled via scheduled job', [
                        'transaction_ref' => $transaction->transaction_ref,
                        'old_status' => $transaction->status,
                        'new_status' => $newStatus,
                        'settlement_status' => $settlementStatus,
                    ]);
                }
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('Reconciliation failed for transaction', [
                    'transaction_ref' => $transaction->transaction_ref,
                    'provider_reference' => $transaction->provider_reference,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        Log::info('Scheduled reconciliation completed', [
            'total_checked' => $staleTransactions->count(),
            'reconciled' => $reconciledCount,
            'timeouts' => $timeoutCount,
            'failed' => $failedCount
        ]);

        return $reconciledCount;
    }

    /**
     * Query provider for transaction status
     * 
     * Calls the payment provider API to get current transaction status.
     * Currently supports PalmPay provider.
     * 
     * @param string $providerReference Provider's transaction reference
     * @return string|null Provider status or null if query failed
     */
    public function queryProviderStatus(string $providerReference): ?string
    {
        try {
            Log::info('Querying provider status', [
                'provider_reference' => $providerReference
            ]);

            $response = $this->palmPayService->queryPayInOrder($providerReference);

            if ($response['success'] && isset($response['data']['status'])) {
                return $response['data']['status'];
            }

            if ($response['success'] && isset($response['data'][0]['status'])) {
                // Handle array response format
                return $response['data'][0]['status'];
            }

            Log::warning('Provider query returned no status', [
                'provider_reference' => $providerReference,
                'response' => $response
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to query provider status', [
                'provider_reference' => $providerReference,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }
}
