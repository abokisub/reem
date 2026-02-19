<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\Financial\FinancialStateService;
use App\Services\PalmPay\VirtualAccountService;
use App\Services\PalmPay\TransferService;
use App\Services\LedgerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * AutoReconcileCommand
 *
 * Runs every 10 minutes to reconcile transactions stuck in 'processing'.
 * Schedule: everyTenMinutes (cron: every 10 minutes)
 */
class AutoReconcileCommand extends Command
{
    protected $signature = 'reconcile:auto';
    protected $description = 'Reconcile processing transactions against the provider every 10 minutes';

    public function handle(FinancialStateService $stateService, LedgerService $ledgerService): int
    {
        Log::info('🔄 AutoReconcile: Starting reconciliation pass...');

        $processingTransactions = Transaction::where('status', 'processing')
            ->where('reconciliation_status', 'pending')
            ->where('provider', 'palmpay')
            ->get();

        $this->info("Found {$processingTransactions->count()} transactions to reconcile.");

        foreach ($processingTransactions as $transaction) {
            $this->reconcileTransaction($transaction, $stateService, $ledgerService);
        }

        Log::info('✅ AutoReconcile: Reconciliation pass complete.');
        return Command::SUCCESS;
    }

    private function reconcileTransaction(
        Transaction $transaction,
        FinancialStateService $stateService,
        LedgerService $ledgerService
    ): void {
        try {
            $vaService = app(VirtualAccountService::class);
            $reference = $transaction->palmpay_reference ?? $transaction->reference;

            $result = $vaService->queryPayInOrder($reference);

            if (!$result['success']) {
                Log::warning("AutoReconcile: Could not query provider for txn {$transaction->transaction_id}");
                return;
            }

            $providerStatus = strtoupper($result['data']['status'] ?? 'UNKNOWN');

            Log::info("AutoReconcile: [{$transaction->transaction_id}] Provider status: {$providerStatus}");

            if ($stateService->isDefinitiveFailure($providerStatus)) {
                // Confirmed failed — safe to reverse
                DB::transaction(function () use ($transaction, $stateService, $ledgerService) {
                    $stateService->transition($transaction, 'failed', [
                        'error_message' => "Reconciliation: Provider confirmed FAILED",
                        'reconciliation_status' => 'not_matched',
                        'reconciled_at' => now(),
                    ]);

                    // Ledger Reversal
                    $companyAccount = $ledgerService->getOrCreateAccount("Company Wallet " . $transaction->company_id, 'company_wallet', $transaction->company_id);
                    $providerAccount = $ledgerService->getOrCreateAccount('PalmPay Clearing', 'bank_clearing');
                    $revenueAccount = $ledgerService->getOrCreateAccount('Platform Revenue', 'revenue');

                    $ledgerService->recordTransaction($transaction->reference . '_RECON_REV', [
                        ['account_id' => $companyAccount->id, 'type' => 'credit', 'amount' => (float) $transaction->total_amount],
                        ['account_id' => $providerAccount->id, 'type' => 'debit', 'amount' => (float) $transaction->amount],
                        ['account_id' => $revenueAccount->id, 'type' => 'debit', 'amount' => (float) $transaction->fee],
                    ], "Reconciliation Reversal: " . $transaction->reference);

                    // Restore wallet balance
                    $wallet = $transaction->company->wallet;
                    $wallet->credit($transaction->total_amount);
                    $wallet->removePending($transaction->total_amount);
                    $wallet->save();
                });

                Log::info("AutoReconcile: Reversed txn {$transaction->transaction_id}");

            } elseif (!$stateService->isAmbiguous($providerStatus)) {
                // Confirmed success
                $stateService->transition($transaction, 'successful', [
                    'reconciliation_status' => 'reconciled',
                    'reconciled_at' => now(),
                ]);
                $wallet = $transaction->company->wallet;
                $wallet->removePending($transaction->total_amount);
                $wallet->save();

                Log::info("AutoReconcile: Confirmed success for txn {$transaction->transaction_id}");
            }

        } catch (\Exception $e) {
            Log::error("AutoReconcile: Failed to reconcile txn {$transaction->transaction_id}", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
