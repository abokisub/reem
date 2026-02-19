<?php

namespace App\Services\Financial;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

/**
 * FinancialStateService
 *
 * Enforces a strict state machine for all transaction status transitions.
 * No transaction can move to an invalid state.
 */
class FinancialStateService
{
    // Valid transitions: from -> [allowed to states]
    private const TRANSITIONS = [
        // Withdrawal lifecycle
        'initiated' => ['debited'],
        'debited' => ['processing', 'failed'],
        'processing' => ['successful', 'failed'],
        'successful' => ['settled'],
        'settled' => [],  // Terminal

        // Deposit lifecycle
        'pending' => ['successful', 'failed'],

        // Reversal
        'failed' => ['reversed'],
        'reversed' => [],  // Terminal
    ];

    // Statuses that indicate a DEFINITIVE provider failure (safe to auto-reverse)
    public const DEFINITIVE_FAILURES = [
        'FAILED',
        'REJECTED',
        'INVALID_ACCOUNT',
        'INSUFFICIENT_FUNDS',
        'ACCOUNT_NOT_FOUND',
        'INVALID_BENEFICIARY',
    ];

    // Statuses that are ambiguous — wait for webhook/reconciliation
    public const AMBIGUOUS_STATUSES = [
        'TIMEOUT',
        'PENDING',
        'PROCESSING',
        'UNKNOWN',
    ];

    /**
     * Transition a transaction to a new status.
     * Throws if the transition is not allowed.
     */
    public function transition(Transaction $transaction, string $newStatus, array $extra = []): Transaction
    {
        $currentStatus = $transaction->status;

        if (!$this->isAllowed($currentStatus, $newStatus)) {
            $message = "Invalid state transition: [{$currentStatus}] → [{$newStatus}] for txn {$transaction->transaction_id}";
            Log::critical($message, ['transaction_id' => $transaction->transaction_id]);
            throw new \Exception($message);
        }

        $updateData = array_merge(['status' => $newStatus], $extra);

        if (in_array($newStatus, ['successful', 'failed', 'reversed', 'settled'])) {
            $updateData['processed_at'] = now();
        }

        $transaction->update($updateData);

        Log::info("Transaction State Transition: [{$currentStatus}] → [{$newStatus}]", [
            'transaction_id' => $transaction->transaction_id,
        ]);

        return $transaction->fresh();
    }

    /**
     * Check if a provider response code is a definitive failure.
     */
    public function isDefinitiveFailure(string $providerStatus): bool
    {
        return in_array(strtoupper($providerStatus), self::DEFINITIVE_FAILURES);
    }

    /**
     * Check if a provider response code is ambiguous (wait-and-see).
     */
    public function isAmbiguous(string $providerStatus): bool
    {
        return in_array(strtoupper($providerStatus), self::AMBIGUOUS_STATUSES);
    }

    /**
     * Check if a transition is valid.
     */
    public function isAllowed(string $from, string $to): bool
    {
        return isset(self::TRANSITIONS[$from]) && in_array($to, self::TRANSITIONS[$from]);
    }
}
