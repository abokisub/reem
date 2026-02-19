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
        return $this->recordTransaction($transactionId, [
            ['account_id' => $debitAccountId, 'type' => 'debit', 'amount' => $amount],
            ['account_id' => $creditAccountId, 'type' => 'credit', 'amount' => $amount],
        ], $description);
    }

    /**
     * Records a complex multi-account transaction.
     * Validates that total debits == total credits.
     */
    public function recordTransaction(string $transactionId, array $movements, string $description = null)
    {
        return DB::transaction(function () use ($transactionId, $movements, $description) {
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($movements as $mov) {
                if ($mov['type'] === 'debit')
                    $totalDebit += $mov['amount'];
                else
                    $totalCredit += $mov['amount'];
            }

            // Simple reconciliation check (Float epsilon handled by rounding)
            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new \Exception("Ledger Unbalanced: Debits ($totalDebit) !== Credits ($totalCredit)");
            }

            $entries = [];
            foreach ($movements as $mov) {
                // Determine if this is a primary entry (for legacy compatibility we still use the entries table)
                // For complex entries, we might need a 'ledger_movements' table, but for now 
                // we store them in ledger_entries with debit/credit account set to null appropriately or 
                // we adapt the table. Since the table has debit_account_id and credit_account_id,
                // complex entries (1:N) require multiple rows or a better schema.

                // DECISION: Map complex entries to multiple standard double-entry rows 
                // by splitting against a temporary balancing account or just recording movements.
                // However, the current schema is LedgerEntry(transaction_id, debit_account_id, credit_account_id, amount).
                // To support 1:N in this schema, we record N entries where the 1 is repeated.
            }

            // Implementation for 1:N or N:1 (Common in Fintech)
            $debits = array_filter($movements, fn($m) => $m['type'] === 'debit');
            $credits = array_filter($movements, fn($m) => $m['type'] === 'credit');

            if (count($debits) === 1) {
                $debit = array_shift($debits);
                foreach ($credits as $credit) {
                    $entries[] = $this->createEntry($transactionId, $debit['account_id'], $credit['account_id'], $credit['amount'], $description);
                }
            } elseif (count($credits) === 1) {
                $credit = array_shift($credits);
                foreach ($debits as $debit) {
                    $entries[] = $this->createEntry($transactionId, $debit['account_id'], $credit['account_id'], $debit['amount'], $description);
                }
            } else {
                // N:M is rare, would require a suspense account. For now, throw error.
                throw new \Exception("N:M Transactions not supported directly. Use a suspense account.");
            }

            return $entries;
        });
    }

    private function createEntry(string $transactionId, int $debitAccountId, int $creditAccountId, float $amount, ?string $description)
    {
        $entry = LedgerEntry::create([
            'transaction_id' => $transactionId,
            'debit_account_id' => $debitAccountId,
            'credit_account_id' => $creditAccountId,
            'amount' => $amount,
            'description' => $description,
        ]);

        $debitAccount = LedgerAccount::lockForUpdate()->find($debitAccountId);
        $debitAccount->decrement('balance', $amount);

        $creditAccount = LedgerAccount::lockForUpdate()->find($creditAccountId);
        $creditAccount->increment('balance', $amount);

        return $entry;
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
