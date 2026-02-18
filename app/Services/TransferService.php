<?php

namespace App\Services;

use App\Models\CompanyWallet;
use App\Models\Transaction;
use App\Models\Company;
use App\Services\LedgerService;
use App\Services\FeeService;
use App\Services\Banking\BankingService;
use Illuminate\Support\Facades\DB;
use Exception;

class TransferService
{
    protected $ledgerService;
    protected $feeService;
    protected $bankingService;

    public function __construct(
        LedgerService $ledgerService,
        FeeService $feeService,
        BankingService $bankingService
    ) {
        $this->ledgerService = $ledgerService;
        $this->feeService = $feeService;
        $this->bankingService = $bankingService;
    }

    /**
     * Process an Internal Wallet-to-Wallet Transfer
     */
    public function processInternalTransfer(CompanyWallet $fromWallet, CompanyWallet $toWallet, float $amount, string $description = null): array
    {
        if ($fromWallet->id === $toWallet->id) {
            throw new Exception("Cannot transfer to self.");
        }

        if ($amount <= 0) {
            throw new Exception("Amount must be greater than zero.");
        }

        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $description) {
            // 1. Lock Sender Wallet
            /** @var CompanyWallet $senderWallet */
            $senderWallet = CompanyWallet::lockForUpdate()->find($fromWallet->id);
            if (!$senderWallet) {
                throw new Exception("Sender wallet not found.");
            }
            if ($senderWallet->balance < $amount) {
                throw new Exception("Insufficient specific funds.");
            }

            // 2. Lock Receiver Wallet
            /** @var CompanyWallet $receiverWallet */
            $receiverWallet = CompanyWallet::lockForUpdate()->find($toWallet->id);
            if (!$receiverWallet) {
                throw new Exception("Receiver wallet not found.");
            }

            // 3. Perform Debits/Credits
            $senderWallet->decrement('balance', $amount);
            $receiverWallet->increment('balance', $amount);

            // 4. Record Transaction for Sender
            $ref = Transaction::generateReference();
            /** @var Transaction $txnSender */
            $txnSender = Transaction::create([
                'company_id' => $senderWallet->company_id,
                'transaction_id' => Transaction::generateTransactionId(),
                'type' => 'transfer',
                'category' => 'transfer_out',
                'amount' => $amount,
                'total_amount' => $amount, // Internal transfer usually no fee? or maybe add fee later
                'status' => 'success',
                'reference' => $ref,
                'description' => $description ?? "Transfer to {$toWallet->company->name}",
                'balance_before' => $senderWallet->balance + $amount, // Revert decr for before
                'balance_after' => $senderWallet->balance,
                'recipient_account_number' => $toWallet->company->id, // Using Company ID as account for internal
                'recipient_account_name' => $toWallet->company->name,
                'processed_at' => now(),
            ]);

            // 5. Record Transaction for Receiver
            /** @var Transaction $txnReceiver */
            $txnReceiver = Transaction::create([
                'company_id' => $receiverWallet->company_id,
                'transaction_id' => Transaction::generateTransactionId(),
                'type' => 'credit', // Credit for receiver
                'category' => 'other', // Or transfer_in if we had it
                'amount' => $amount,
                'total_amount' => $amount,
                'status' => 'success',
                'reference' => 'INC-' . $ref,
                'description' => "Transfer from {$senderWallet->company->name}",
                'balance_before' => $receiverWallet->balance - $amount,
                'balance_after' => $receiverWallet->balance,
                'processed_at' => now(),
            ]);

            // 6. Ledger Posting
            $ledgerLink = $this->ledgerService->getOrCreateAccount('Internal Transfer Clearing', 'clearing');

            // Debit Sender Wallet GL, Credit Clearing
            $senderGL = $this->ledgerService->getOrCreateAccount($senderWallet->company->name . ' Wallet', 'company_wallet', $senderWallet->company_id);
            $receiverGL = $this->ledgerService->getOrCreateAccount($receiverWallet->company->name . ' Wallet', 'company_wallet', $receiverWallet->company_id);

            /** @var Transaction $txnSender */
            $senderCompany = $senderWallet->company;
            $receiverCompany = $receiverWallet->company;

            $this->ledgerService->recordEntry(
                $txnSender->reference,
                $senderGL->id, // Debit Liability (Balance Down) -> wait, Liability Down is Debit? Yes.
                $receiverGL->id, // Credit Liability (Balance Up) -> Yes.
                $amount,
                "Internal Transfer: {$senderCompany->name} to {$receiverCompany->name}"
            );

            return [
                'status' => 'success',
                'message' => 'Transfer successful',
                'transaction' => $txnSender
            ];
        });
    }

    /**
     * Process External Transfer (Payout to Bank)
     */
    public function processExternalTransfer(CompanyWallet $fromWallet, array $bankDetails, float $amount): array
    {
        // 1. Calculate Fee
        $feeData = $this->feeService->calculateFee($fromWallet->company_id, $amount);
        $totalDeduction = $amount + $feeData['fee']; // Sender pays fee

        return DB::transaction(function () use ($fromWallet, $bankDetails, $amount, $totalDeduction, $feeData) {
            // 2. Lock Wallet
            /** @var CompanyWallet $wallet */
            $wallet = CompanyWallet::lockForUpdate()->find($fromWallet->id);
            if (!$wallet) {
                throw new Exception("Wallet not found.");
            }

            if ($wallet->balance < $totalDeduction) {
                throw new Exception("Insufficient funds for transfer + fee.");
            }

            // 3. Create Pending Transaction
            $ref = Transaction::generateReference();
            /** @var Transaction $txn */
            $txn = Transaction::create([
                'company_id' => $wallet->company_id,
                'transaction_id' => Transaction::generateTransactionId(),
                'type' => 'debit',
                'category' => 'transfer_out',
                'amount' => $amount,
                'fee' => $feeData['fee'],
                'total_amount' => $totalDeduction,
                'status' => 'pending', // Pending until provider confirms
                'reference' => $ref,
                'description' => "Transfer to {$bankDetails['account_number']} - {$bankDetails['bank_code']}",
                'recipient_account_number' => $bankDetails['account_number'],
                'recipient_account_name' => $bankDetails['account_name'] ?? null,
                'recipient_bank_code' => $bankDetails['bank_code'],
                'balance_before' => $wallet->balance,
                'balance_after' => $wallet->balance - $totalDeduction, // We deduct immediately?
            ]);

            // 4. Deduct Balance Immediately (Conservative Approach)
            $wallet->decrement('balance', $totalDeduction);

            // 5. Ledger (Debit Wallet, Credit Pending Transfer / Clearing)
            /** @var \App\Models\LedgerAccount $walletGL */
            $company = $wallet->company;
            $walletGL = $this->ledgerService->getOrCreateAccount($company->name . ' Wallet', 'company_wallet', $wallet->company_id);
            $clearingGL = $this->ledgerService->getOrCreateAccount('Pending Payout Clearing', 'clearing');
            $revenueGL = $this->ledgerService->getOrCreateAccount('Transfer Fee Revenue', 'revenue');

            // Principal
            /** @var Transaction $txn */
            $this->ledgerService->recordEntry(
                $txn->reference,
                $walletGL->id,
                $clearingGL->id,
                $amount,
                "Payout Principal: $ref"
            );

            // Fee
            if ($feeData['fee'] > 0) {
                $this->ledgerService->recordEntry(
                    $txn->reference . '-FEE',
                    $walletGL->id,
                    $revenueGL->id,
                    $feeData['fee'],
                    "Payout Fee: $ref"
                );
            }

            // 6. Call Provider (BankingService / PalmPay)
            // Note: In a real system, this might be async (Job). For now we do sync or mock.
            try {
                $providerResult = $this->bankingService->transfer([
                    'amount' => $amount,
                    'account_number' => $bankDetails['account_number'],
                    'bank_code' => $bankDetails['bank_code'],
                    'reference' => $ref
                ]);

                if ($providerResult['status'] === 'success') {
                    /** @var Transaction $txn */
                    $txn->update(['status' => 'success', 'processed_at' => now()]);
                } elseif ($providerResult['status'] === 'failed') {
                    // Reverse
                    throw new Exception("Provider failed: " . ($providerResult['message'] ?? 'Unknown'));
                }
                // If pending, leave as pending

            } catch (Exception $e) {
                // If provider call crashes, we roll back the DB transaction so money is not lost.
                // Or we catch and refund. Since we are inside DB::transaction, throwing will rollback everything.
                throw $e;
            }

            return [
                'status' => $txn->status,
                'message' => 'Transfer processing initiated',
                'transaction' => $txn
            ];
        });
    }
}
