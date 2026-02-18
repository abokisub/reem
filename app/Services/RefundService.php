<?php

namespace App\Services;

use App\Models\Refund;
use App\Models\Transaction;
use App\Models\CompanyWallet;
use App\Services\LedgerService;
use App\Services\PalmPay\RefundService as PalmPayRefundService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Core Refund Service
 * Handles auto and manual refunds with wallet/ledger reversals
 */
class RefundService
{
    protected $ledgerService;
    protected $palmPayRefundService;

    public function __construct(
        LedgerService $ledgerService,
        PalmPayRefundService $palmPayRefundService
    ) {
        $this->ledgerService = $ledgerService;
        $this->palmPayRefundService = $palmPayRefundService;
    }

    /**
     * Process automatic refund for failed/stale transactions
     */
    public function processAutoRefund(Transaction $transaction, string $reason = 'Auto-refund: Transaction failed'): array
    {
        if (!in_array($transaction->type, ['debit', 'transfer'])) {
            throw new Exception("Cannot refund transaction type: {$transaction->type}");
        }

        if ($transaction->status !== 'failed') {
            throw new Exception("Cannot refund transaction with status: {$transaction->status}");
        }

        return DB::transaction(function () use ($transaction, $reason) {
            // 1. Create refund record
            $refundId = 'REF-AUTO-' . strtoupper(uniqid());

            $refund = Refund::create([
                'company_id' => $transaction->company_id,
                'refund_id' => $refundId,
                'transaction_id' => $transaction->reference,
                'palmpay_order_no' => $transaction->palmpay_reference,
                'amount' => $transaction->total_amount, // Refund full amount including fees
                'currency' => $transaction->currency,
                'reason' => $reason,
                'refund_type' => 'auto',
                'status' => 'processing',
                'initiated_at' => now(),
            ]);

            // 2. Reverse wallet deduction (credit back to company)
            $wallet = CompanyWallet::lockForUpdate()
                ->where('company_id', $transaction->company_id)
                ->where('currency', $transaction->currency)
                ->first();

            if (!$wallet) {
                throw new Exception("Wallet not found for company {$transaction->company_id}");
            }

            $wallet->increment('balance', $transaction->total_amount);

            // 3. Reverse ledger entries
            $this->reverseLedgerEntries($transaction, $refundId);

            // 4. Update transaction status
            $transaction->update([
                'status' => 'reversed',
                'error_message' => $reason,
            ]);

            // 5. Mark refund as completed
            $refund->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            Log::info('Auto-refund processed', [
                'refund_id' => $refundId,
                'transaction_id' => $transaction->reference,
                'amount' => $transaction->total_amount,
            ]);

            return [
                'success' => true,
                'refund' => $refund,
                'message' => 'Auto-refund processed successfully',
            ];
        });
    }

    /**
     * Process manual refund initiated by admin
     */
    public function processManualRefund(
        Transaction $transaction,
        string $reason,
        int $adminId,
        ?string $adminNotes = null
    ): array {
        if ($transaction->status !== 'success') {
            throw new Exception("Can only manually refund successful transactions");
        }

        // Check if already refunded
        $existingRefund = Refund::where('transaction_id', $transaction->reference)
            ->whereIn('status', ['completed', 'processing'])
            ->first();

        if ($existingRefund) {
            throw new Exception("Transaction already refunded: {$existingRefund->refund_id}");
        }

        return DB::transaction(function () use ($transaction, $reason, $adminId, $adminNotes) {
            // 1. Create refund record
            $refundId = 'REF-MAN-' . strtoupper(uniqid());

            $refund = Refund::create([
                'company_id' => $transaction->company_id,
                'refund_id' => $refundId,
                'transaction_id' => $transaction->reference,
                'palmpay_order_no' => $transaction->palmpay_reference,
                'amount' => $transaction->amount, // Refund principal only (not fees for manual)
                'currency' => $transaction->currency,
                'reason' => $reason,
                'refund_type' => 'manual',
                'initiated_by' => $adminId,
                'admin_notes' => $adminNotes,
                'status' => 'processing',
                'initiated_at' => now(),
            ]);

            // 2. For deposits: Debit company wallet
            if ($transaction->type === 'credit') {
                $wallet = CompanyWallet::lockForUpdate()
                    ->where('company_id', $transaction->company_id)
                    ->where('currency', $transaction->currency)
                    ->first();

                if (!$wallet) {
                    throw new Exception("Wallet not found");
                }

                if ($wallet->balance < $transaction->amount) {
                    throw new Exception("Insufficient balance for refund");
                }

                $wallet->decrement('balance', $transaction->amount);

                // Ledger: Debit Company Wallet, Credit Bank Clearing (reversal)
                $walletGL = $this->ledgerService->getOrCreateAccount(
                    "Company Wallet {$transaction->company_id}",
                    'company_wallet',
                    $transaction->company_id
                );
                $clearingGL = $this->ledgerService->getOrCreateAccount('PalmPay Clearing', 'bank_clearing');

                $this->ledgerService->recordEntry(
                    $refundId,
                    $walletGL->id,
                    $clearingGL->id,
                    $transaction->amount,
                    "Manual Refund: {$reason}"
                );
            }

            // 3. Initiate PalmPay refund if applicable
            if ($transaction->palmpay_reference) {
                try {
                    $this->palmPayRefundService->initiateRefund($transaction->company_id, [
                        'transaction_id' => $transaction->reference,
                        'palmpay_order_no' => $transaction->palmpay_reference,
                        'amount' => $transaction->amount,
                        'currency' => $transaction->currency,
                        'reason' => $reason,
                    ]);
                } catch (Exception $e) {
                    Log::error('PalmPay refund initiation failed', [
                        'refund_id' => $refundId,
                        'error' => $e->getMessage(),
                    ]);
                    // Continue with local refund even if PalmPay fails
                }
            }

            // 4. Mark refund as completed
            $refund->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            Log::info('Manual refund processed', [
                'refund_id' => $refundId,
                'transaction_id' => $transaction->reference,
                'admin_id' => $adminId,
                'amount' => $transaction->amount,
            ]);

            return [
                'success' => true,
                'refund' => $refund,
                'message' => 'Manual refund processed successfully',
            ];
        });
    }

    /**
     * Reverse ledger entries for a failed transaction
     */
    protected function reverseLedgerEntries(Transaction $transaction, string $refundId): void
    {
        $walletGL = $this->ledgerService->getOrCreateAccount(
            "Company Wallet {$transaction->company_id}",
            'company_wallet',
            $transaction->company_id
        );

        if ($transaction->category === 'transfer_out') {
            // Reverse: Debit Pending Payout Clearing, Credit Company Wallet
            $clearingGL = $this->ledgerService->getOrCreateAccount('Pending Payout Clearing', 'clearing');

            $this->ledgerService->recordEntry(
                $refundId,
                $clearingGL->id,
                $walletGL->id,
                $transaction->amount,
                "Refund Reversal: {$transaction->reference}"
            );

            // Reverse fee if applicable
            if ($transaction->fee > 0) {
                $revenueGL = $this->ledgerService->getOrCreateAccount('Transfer Fee Revenue', 'revenue');
                $this->ledgerService->recordEntry(
                    $refundId . '-FEE',
                    $revenueGL->id,
                    $walletGL->id,
                    $transaction->fee,
                    "Fee Refund: {$transaction->reference}"
                );
            }
        }
    }

    /**
     * Get refunds for a company with filters
     */
    public function getRefunds(int $companyId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = Refund::where('company_id', $companyId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['refund_type'])) {
            $query->where('refund_type', $filters['refund_type']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
