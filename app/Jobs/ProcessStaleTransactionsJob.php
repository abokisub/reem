<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Services\RefundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Process Stale Transactions Job
 * 
 * Detects transactions stuck in 'processing' status for >24 hours
 * and triggers auto-refunds
 */
class ProcessStaleTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(RefundService $refundService): void
    {
        Log::info('ProcessStaleTransactionsJob: Starting stale transaction check');

        // Find transactions stuck in 'processing' for more than 24 hours
        $staleTransactions = Transaction::where('status', 'processing')
            ->where('created_at', '<', Carbon::now()->subHours(24))
            ->whereIn('type', ['debit', 'transfer']) // Only refundable types
            ->get();

        Log::info('ProcessStaleTransactionsJob: Found stale transactions', [
            'count' => $staleTransactions->count()
        ]);

        foreach ($staleTransactions as $transaction) {
            try {
                $result = $refundService->processAutoRefund(
                    $transaction,
                    'Auto-refund: Transaction timeout (>24hrs in processing)'
                );

                Log::info('ProcessStaleTransactionsJob: Auto-refund successful', [
                    'transaction_id' => $transaction->reference,
                    'refund_id' => $result['refund']->refund_id,
                ]);

            } catch (\Exception $e) {
                Log::error('ProcessStaleTransactionsJob: Auto-refund failed', [
                    'transaction_id' => $transaction->reference,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('ProcessStaleTransactionsJob: Completed');
    }
}
