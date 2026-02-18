<?php

namespace App\Services;

use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LedgerService
{
    /**
     * Records an atomic double-entry movement.
     * Rule: Every debit must have a corresponding credit.
     */
    public function recordEntry(string $transactionId, int $debitAccountId, int $creditAccountId, float $amount, string $description = null)
    {
        return DB::transaction(function () use ($transactionId, $debitAccountId, $creditAccountId, $amount, $description) {
            // 1. Create Entry
            $entry = LedgerEntry::create([
                'transaction_id' => $transactionId,
                'debit_account_id' => $debitAccountId,
                'credit_account_id' => $creditAccountId,
                'amount' => $amount,
                'description' => $description,
            ]);

            // 2. Update Debit Account Balance (Decrease)
            $debitAccount = LedgerAccount::lockForUpdate()->find($debitAccountId);
            $debitAccount->decrement('balance', $amount);

            // 3. Update Credit Account Balance (Increase)
            $creditAccount = LedgerAccount::lockForUpdate()->find($creditAccountId);
            $creditAccount->increment('balance', $amount);

            return $entry;
        });
    }

    /**
     * Gets or creates a ledger account for a specific entity/type.
     */
    public function getOrCreateAccount(string $name, string $type, ?int $companyId = null, string $currency = 'NGN')
    {
        return LedgerAccount::firstOrCreate(
            [
                'account_type' => $type,
                'company_id' => $companyId,
                'currency' => $currency,
            ],
            [
                'uuid' => 'PWV_ACC_' . strtoupper(Str::random(10)),
                'name' => $name,
            ]
        );
    }
}
