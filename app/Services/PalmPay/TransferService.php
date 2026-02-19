<?php

namespace App\Services\PalmPay;

use App\Models\Transaction;
use App\Models\CompanyWallet;
use App\Services\PalmPay\PalmPayClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PalmPay Transfer Service
 * 
 * Handles bank transfers via PalmPay
 */
class TransferService
{
    private PalmPayClient $client;
    private \App\Services\LedgerService $ledgerService;
    private \App\Services\FeeService $feeService;
    private \App\Services\Financial\FinancialStateService $stateService;

    public function __construct(
        \App\Services\LedgerService $ledgerService,
        \App\Services\FeeService $feeService,
        \App\Services\Financial\FinancialStateService $stateService
    ) {
        $this->client = new PalmPayClient();
        $this->ledgerService = $ledgerService;
        $this->feeService = $feeService;
        $this->stateService = $stateService;
    }

    /**
     * Initiate a bank transfer
     * 
     * @param int $companyId
     * @param array $transferData
     * @return Transaction
     */
    public function initiateTransfer(int $companyId, array $transferData): Transaction
    {
        try {
            return DB::transaction(function () use ($companyId, $transferData) {
                // Get company wallet
                $wallet = CompanyWallet::where('company_id', $companyId)
                    ->where('currency', 'NGN')
                    ->lockForUpdate()
                    ->firstOrFail();

                // 1. Calculate Charges BEFORE provider call
                $amount = $transferData['amount'];
                $feeResults = $this->feeService->calculateFee($companyId, $amount);
                $fee = (float) $feeResults['fee'];
                $totalAmount = $amount + $fee;

                // 2. Strict Balance Check
                if ($wallet->balance < $totalAmount) {
                    throw new \Exception('Insufficient balance to cover amount and fees');
                }

                // 3. Generate Secure References (Never Null)
                $transactionId = Transaction::generateTransactionId();
                $reference = Transaction::generateReference() ?? 'PWV_' . strtoupper(Str::random(12));

                $balanceBefore = $wallet->balance;

                // 4. Record Double Entry Ledger (Atomic)
                // Debit: Company Wallet (Total)
                // Credit: Provider Clearing (Amount)
                // Credit: Platform Revenue (Fee)

                $companyAccount = $this->ledgerService->getOrCreateAccount("Company Wallet $companyId", 'company_wallet', $companyId);
                $providerAccount = $this->ledgerService->getOrCreateAccount('PalmPay Clearing', 'bank_clearing');
                $revenueAccount = $this->ledgerService->getOrCreateAccount('Platform Revenue', 'revenue');

                $this->ledgerService->recordTransaction($reference, [
                    ['account_id' => $companyAccount->id, 'type' => 'debit', 'amount' => $totalAmount],
                    ['account_id' => $providerAccount->id, 'type' => 'credit', 'amount' => $amount],
                    ['account_id' => $revenueAccount->id, 'type' => 'credit', 'amount' => $fee],
                ], "Bank Transfer to " . ($transferData['bank_name'] ?? 'Unknown'));

                // 5. Sync Legacy CompanyWallet Table (Balance Integrity)
                $wallet->decrement('balance', $totalAmount);
                $wallet->decrement('ledger_balance', $totalAmount);
                $wallet->save();

                // 6. Sync System Wallets (Revenue & Clearing Isolation)
                $revWallet = \App\Models\SystemWallet::where('slug', 'platform_revenue')->lockForUpdate()->first();
                if ($revWallet)
                    $revWallet->credit($fee);

                $clrWallet = \App\Models\SystemWallet::where('slug', 'bank_clearing')->lockForUpdate()->first();
                if ($clrWallet)
                    $clrWallet->credit($amount);

                // 7. Create Transaction Record
                $transaction = Transaction::create([
                    'transaction_id' => $transactionId,
                    'company_id' => $companyId,
                    'type' => 'debit',
                    'category' => 'transfer_out',
                    'amount' => $amount,
                    'fee' => $fee,
                    'net_amount' => $amount,
                    'total_amount' => $totalAmount,
                    'currency' => 'NGN',
                    'status' => 'pending',
                    'reference' => $reference,
                    'external_reference' => $transferData['reference'] ?? null,
                    'recipient_account_number' => $transferData['account_number'],
                    'recipient_account_name' => $transferData['account_name'] ?? null,
                    'recipient_bank_code' => $transferData['bank_code'],
                    'recipient_bank_name' => $transferData['bank_name'] ?? null,
                    'description' => $transferData['narration'] ?? 'Bank Transfer',
                    'metadata' => $transferData['metadata'] ?? null,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $wallet->balance,
                    'provider' => 'palmpay',
                    'reconciliation_status' => 'not_started',
                ]);

                // 7. Async Provider Call (Triggered after commit)
                DB::afterCommit(function () use ($transaction) {
                    $this->processPalmPayTransfer($transaction);
                });

                return $transaction;
            });

        } catch (\Exception $e) {
            Log::error('Failed to Initiate Transfer (Ledger Error)', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Process transfer with PalmPay
     * 
     * @param Transaction $transaction
     * @return void
     */
    private function processPalmPayTransfer(Transaction $transaction): void
    {
        try {
            // Transition: debited → processing
            $this->stateService->transition($transaction, 'processing');

            $requestData = [
                'orderId' => $transaction->reference,
                'payeeBankCode' => $transaction->recipient_bank_code,
                'payeeBankAccNo' => $transaction->recipient_account_number,
                'amount' => (int) ($transaction->amount * 100),
                'currency' => 'NGN',
                'notifyUrl' => config('app.url') . '/api/v1/palmpay/webhook/payout',
                'remark' => $transaction->description ?? 'Transfer',
                'payeeName' => $transaction->recipient_account_name ?? 'Unknown',
            ];

            Log::info('Processing PalmPay Transfer', ['transaction_id' => $transaction->transaction_id]);

            $response = $this->client->post('/api/v2/merchant/payment/payout', $requestData);

            $palmpayReference = $response['data']['orderNo'] ?? null;
            $providerStatus = strtoupper($response['data']['orderStatus'] ?? 'UNKNOWN');

            // --- SAFE MODE: Route by provider status ---
            if ($this->stateService->isDefinitiveFailure($providerStatus)) {
                // Definitive failure — immediately reverse
                Log::warning('SAFE MODE: Definitive failure from provider. Triggering reversal.', [
                    'status' => $providerStatus,
                    'transaction_id' => $transaction->transaction_id
                ]);
                $this->handleFailedTransfer($transaction, "Provider: {$providerStatus}");

            } elseif ($this->stateService->isAmbiguous($providerStatus)) {
                // Ambiguous — hold in processing, wait for webhook/reconciliation
                $transaction->update([
                    'palmpay_reference' => $palmpayReference,
                    'reconciliation_status' => 'pending',
                    'provider_reference' => $palmpayReference,
                ]);
                Log::info('SAFE MODE: Ambiguous status. Holding in processing.', [
                    'status' => $providerStatus,
                    'transaction_id' => $transaction->transaction_id
                ]);

            } else {
                // Successful
                $this->stateService->transition($transaction, 'successful', [
                    'palmpay_reference' => $palmpayReference,
                    'provider_reference' => $palmpayReference,
                    'reconciliation_status' => 'reconciled',
                ]);
                $wallet = $transaction->fresh()->company->wallet;
                $wallet->removePending($transaction->total_amount);
                $wallet->save();
            }

        } catch (\Exception $e) {
            Log::error('PalmPay Transfer Exception', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage()
            ]);
            // Exceptions (network, 500) are ambiguous — don't reverse blindly
            $transaction->update(['reconciliation_status' => 'pending']);
        }
    }

    /**
     * Handle failed transfer (refund)
     * 
     * @param Transaction $transaction
     * @param string $errorMessage
     * @return void
     */
    private function handleFailedTransfer(Transaction $transaction, string $errorMessage): void
    {
        try {
            DB::beginTransaction();

            // Strict state transition via FinancialStateService
            $this->stateService->transition($transaction, 'failed', [
                'error_message' => $errorMessage,
                'reconciliation_status' => 'not_matched',
            ]);

            // 1. Ledger Reversal (High Integrity)
            // Initial: Debit Wallet (Total), Credit Clearing (Amount), Credit Revenue (Fee)
            // Reversal: Credit Wallet (Total), Debit Clearing (Amount), Debit Revenue (Fee)

            $companyAccount = $this->ledgerService->getOrCreateAccount("Company Wallet " . $transaction->company_id, 'company_wallet', $transaction->company_id);
            $providerAccount = $this->ledgerService->getOrCreateAccount('PalmPay Clearing', 'bank_clearing');
            $revenueAccount = $this->ledgerService->getOrCreateAccount('Platform Revenue', 'revenue');

            $this->ledgerService->recordTransaction($transaction->reference . '_REV', [
                ['account_id' => $companyAccount->id, 'type' => 'credit', 'amount' => (float) $transaction->total_amount],
                ['account_id' => $providerAccount->id, 'type' => 'debit', 'amount' => (float) $transaction->amount],
                ['account_id' => $revenueAccount->id, 'type' => 'debit', 'amount' => (float) $transaction->fee],
            ], "Reversal of Failed Transfer: " . $transaction->reference);

            // 2. Refund to Legacy Wallet Balance
            $wallet = $transaction->company->wallet;
            $wallet->credit($transaction->total_amount);
            $wallet->removePending($transaction->total_amount);
            $wallet->save();

            // 3. Sync System Wallets Reversal
            $revWallet = \App\Models\SystemWallet::where('slug', 'platform_revenue')->lockForUpdate()->first();
            if ($revWallet)
                $revWallet->debit($transaction->fee);

            $clrWallet = \App\Models\SystemWallet::where('slug', 'bank_clearing')->lockForUpdate()->first();
            if ($clrWallet)
                $clrWallet->debit($transaction->amount);

            DB::commit();

            Log::info('Transfer Refunded & Ledger Reversed', [
                'transaction_id' => $transaction->transaction_id,
                'reference' => $transaction->reference
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::critical('Failed to Refund Transaction', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Query transfer status from PalmPay
     * 
     * @param string $reference
     * @return array
     */
    public function queryTransferStatus(string $reference): array
    {
        try {
            // Path: /api/v2/merchant/payment/queryPayStatus
            $response = $this->client->post('/api/v2/merchant/payment/queryPayStatus', [
                'orderId' => $reference
            ]);

            return $response['data'] ?? [];

        } catch (\Exception $e) {
            Log::error('Failed to Query Transfer Status', [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get list of supported banks from PalmPay
     * 
     * @return array
     */
    public function getBankList(): array
    {
        try {
            // Correct endpoint for general bank list
            $response = $this->client->post('/api/v2/general/merchant/queryBankList', [
                'requestTime' => (int) (microtime(true) * 1000),
            ]);

            return $response['data'] ?? [];

        } catch (\Exception $e) {
            Log::error('Failed to Query Bank List', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Get merchant balance from PalmPay
     * 
     * @return array
     */
    public function getBalance(): array
    {
        try {
            // Path: /api/v2/merchant/payment/payout/queryBalance
            $response = $this->client->post('/api/v2/merchant/payment/payout/queryBalance', [
                'currency' => 'NGN'
            ]);

            return $response['data'] ?? [];

        } catch (\Exception $e) {
            Log::error('Failed to Query PalmPay Balance', [
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function mapPalmPayStatus(string $palmpayStatus): string
    {
        $status = strtolower($palmpayStatus);

        switch ($status) {
            case 'success':
            case 'successful':
            case 'completed':
                return 'success';

            case 'failed':
            case 'declined':
                return 'failed';

            case 'pending':
            case 'processing':
                return 'processing';

            case 'reversed':
                return 'reversed';

            default:
                return 'pending';
        }
    }
}