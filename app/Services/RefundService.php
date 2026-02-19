<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\CompanyWallet;
use App\Services\LedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RefundService
{
    private LedgerService $ledgerService;

    public function __construct(LedgerService $ledgerService)
    {
        $this->ledgerService = $ledgerService;
    }

    /**
     * Process a refund for a successful deposit (R.A Credit)
     * Rule: Debit Company Wallet (Net) | Debit Revenue (Fee) | Credit Provider Clearing (Gross)
     */
    public function refundDeposit(Transaction $originalTx, string $reason = 'Customer Refund')
    {
        return DB::transaction(function () use ($originalTx, $reason) {
            if ($originalTx->type !== 'credit' || $originalTx->status !== 'success') {
                throw new \Exception('Only successful credit transactions can be refunded.');
            }

            if ($originalTx->is_refunded) {
                throw new \Exception('Transaction already refunded.');
            }

            // 1. Create Reversal Transaction (New Record)
            $refundRef = 'REF_' . strtoupper(Str::random(12));
            $refundTx = Transaction::create([
                'transaction_id' => Transaction::generateTransactionId(),
                'company_id' => $originalTx->company_id,
                'type' => 'debit', // Reversal of credit is a debit
                'category' => 'refund_reversal',
                'amount' => $originalTx->amount,
                'fee' => $originalTx->fee,
                'net_amount' => $originalTx->net_amount,
                'total_amount' => $originalTx->total_amount,
                'currency' => $originalTx->currency,
                'status' => 'success',
                'reference' => $refundRef,
                'description' => "REVERSAL: " . $originalTx->reference . " - " . $reason,
                'metadata' => [
                    'original_reference' => $originalTx->reference,
                    'refund_reason' => $reason
                ],
                'processed_at' => now(),
            ]);

            // 2. Ledger Reversal
            $companyAccount = $this->ledgerService->getOrCreateAccount("Company Wallet " . $originalTx->company_id, 'company_wallet', $originalTx->company_id);
            $providerAccount = $this->ledgerService->getOrCreateAccount('PalmPay Clearing', 'bank_clearing');
            $revenueAccount = $this->ledgerService->getOrCreateAccount('Platform Revenue', 'revenue');

            // DEBIT Company Wallet (Net)
            // DEBIT Revenue (Fee)
            // CREDIT Provider Clearing (Total)
            $this->ledgerService->recordTransaction($refundRef, [
                ['account_id' => $companyAccount->id, 'type' => 'debit', 'amount' => $originalTx->net_amount],
                ['account_id' => $revenueAccount->id, 'type' => 'debit', 'amount' => $originalTx->fee],
                ['account_id' => $providerAccount->id, 'type' => 'credit', 'amount' => $originalTx->amount],
            ], "Refund of " . $originalTx->reference);

            // 3. Mark Original as Reversed
            $originalTx->update([
                'status' => 'reversed',
                'is_refunded' => true,
                'refund_transaction_id' => $refundTx->id
            ]);

            // 4. Sync Legacy Balance (Debit company wallet)
            $wallet = CompanyWallet::where('company_id', $originalTx->company_id)->first();
            if ($wallet) {
                $wallet->decrement('balance', (float) $originalTx->net_amount);
                $wallet->decrement('ledger_balance', (float) $originalTx->net_amount);
                $wallet->save();
            }

            return $refundTx;
        });
    }
}
