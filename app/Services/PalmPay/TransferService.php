<?php

namespace App\Services\PalmPay;

use App\Models\Transaction;
use App\Models\CompanyWallet;
use App\Services\PalmPay\PalmPayClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PalmPay Transfer Service
 * 
 * Handles bank transfers via PalmPay
 */
class TransferService
{
    private PalmPayClient $client;

    public function __construct()
    {
        $this->client = new PalmPayClient();
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
            DB::beginTransaction();

            // Get company wallet
            $wallet = CompanyWallet::where('company_id', $companyId)
                ->where('currency', 'NGN')
                ->lockForUpdate()
                ->firstOrFail();

            // Calculate total amount (amount + fee)
            $amount = $transferData['amount'];
            $fee = $this->calculateFee($amount, $companyId);
            $totalAmount = $amount + $fee;

            // Check balance
            if ($wallet->balance < $totalAmount) {
                throw new \Exception('Insufficient balance');
            }

            // Generate transaction IDs
            $transactionId = Transaction::generateTransactionId();
            $reference = Transaction::generateReference();

            // Record balance before
            $balanceBefore = $wallet->balance;

            // Create transaction record
            $transaction = Transaction::create([
                'transaction_id' => $transactionId,
                'company_id' => $companyId,
                'type' => 'debit',
                'category' => 'transfer_out',
                'amount' => $amount,
                'fee' => $fee,
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
            ]);

            // Deduct from wallet and add to pending
            $wallet->debit($totalAmount);
            $wallet->addPending($totalAmount);
            $wallet->save();

            // Update balance after
            $transaction->update(['balance_after' => $wallet->balance]);

            DB::commit();

            // Call PalmPay API asynchronously
            $this->processPalmPayTransfer($transaction);

            return $transaction;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to Initiate Transfer', [
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
            $transaction->update(['status' => 'processing']);

            // Prepare request data
            // Documentation requirements:
            // orderId, payeeBankCode, payeeBankAccNo, amount, currency, notifyUrl, remark
            $requestData = [
                'orderId' => $transaction->reference, // Our reference as orderId
                'payeeBankCode' => $transaction->recipient_bank_code,
                'payeeBankAccNo' => $transaction->recipient_account_number,
                'amount' => (int) ($transaction->amount * 100), // Convert to kobo (smallest unit)
                'currency' => 'NGN',
                'notifyUrl' => config('app.url') . '/api/v1/palmpay/webhook/payout',
                'remark' => $transaction->description ?? 'Transfer',
                'payeeName' => $transaction->recipient_account_name ?? 'Unknown',
            ];

            Log::info('Processing PalmPay Transfer', [
                'transaction_id' => $transaction->transaction_id,
                'data' => $requestData
            ]);

            // Call PalmPay API
            // Path: /api/v2/merchant/payment/payout
            $response = $this->client->post('/api/v2/merchant/payment/payout', $requestData);

            // Extract PalmPay reference
            $palmpayReference = $response['data']['orderNo'] ?? null;
            $status = $response['data']['orderStatus'] ?? 'pending';

            // Update transaction
            $transaction->update([
                'palmpay_reference' => $palmpayReference,
                'status' => $this->mapPalmPayStatus($status),
                'processed_at' => now(),
            ]);

            // Remove from pending if successful
            if ($transaction->status === 'success') {
                $wallet = $transaction->company->wallet;
                $wallet->removePending($transaction->total_amount);
                $wallet->save();
            }

            Log::info('PalmPay Transfer Processed', [
                'transaction_id' => $transaction->transaction_id,
                'status' => $transaction->status,
                'palmpay_reference' => $palmpayReference
            ]);

        } catch (\Exception $e) {
            Log::error('PalmPay Transfer Failed', [
                'transaction_id' => $transaction->transaction_id,
                'error' => $e->getMessage()
            ]);

            // Mark as failed and refund
            $this->handleFailedTransfer($transaction, $e->getMessage());
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

            // Update transaction status
            $transaction->update([
                'status' => 'failed',
                'error_message' => $errorMessage,
                'processed_at' => now(),
            ]);

            // Refund to wallet
            $wallet = $transaction->company->wallet;
            $wallet->credit($transaction->total_amount);
            $wallet->removePending($transaction->total_amount);
            $wallet->save();

            DB::commit();

            Log::info('Transfer Refunded', [
                'transaction_id' => $transaction->transaction_id
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

    /**
     * Calculate transfer fee
     * 
     * @param float $amount
     * @return float
     */
    private function calculateFee(float $amount, int $companyId): float
    {
        $settings = DB::table('settings')->where('company_id', $companyId)->first();
        if (!$settings) {
            $settings = DB::table('settings')->where('company_id', 1)->first();
        }

        if (!$settings) {
            return 0.00;
        }

        $type = $settings->payout_palmpay_charge_type ?? 'FLAT';
        $val = $settings->payout_palmpay_charge_value ?? 0;
        $cap = $settings->payout_palmpay_charge_cap ?? 0;

        if ($type == 'PERCENTAGE') {
            $charge = ($amount / 100) * $val;
            if ($cap > 0 && $charge > $cap) {
                $charge = $cap;
            }
            return (float) $charge;
        }

        return (float) $val;
    }

    /**
     * Map PalmPay status to our status
     * 
     * @param string $palmpayStatus
     * @return string
     */
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