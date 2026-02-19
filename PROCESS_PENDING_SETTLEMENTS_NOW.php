<?php

/**
 * Emergency Settlement Processor
 * 
 * This script manually processes ALL pending settlements immediately
 * Use this when settlements are stuck in the queue
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\CompanyWallet;
use App\Models\Transaction;

echo "\n";
echo "========================================\n";
echo "Emergency Settlement Processor\n";
echo "========================================\n";
echo "\n";

try {
    // Get all pending settlements
    $pendingSettlements = DB::table('settlement_queue')
        ->where('status', 'pending')
        ->orderBy('created_at')
        ->get();

    if ($pendingSettlements->isEmpty()) {
        echo "✓ No pending settlements found\n";
        echo "\n";
        exit(0);
    }

    echo "Found {$pendingSettlements->count()} pending settlements\n";
    echo "\n";

    $processed = 0;
    $failed = 0;
    $totalAmount = 0;

    foreach ($pendingSettlements as $settlement) {
        echo "Processing settlement #{$settlement->id}...\n";
        echo "  Company ID: {$settlement->company_id}\n";
        echo "  Amount: ₦" . number_format($settlement->amount, 2) . "\n";
        echo "  Scheduled: {$settlement->scheduled_settlement_date}\n";

        try {
            DB::beginTransaction();

            // Mark as processing
            DB::table('settlement_queue')
                ->where('id', $settlement->id)
                ->update(['status' => 'processing']);

            // Get transaction
            $transaction = Transaction::find($settlement->transaction_id);
            
            if (!$transaction) {
                throw new \Exception("Transaction not found: {$settlement->transaction_id}");
            }

            // Get company wallet
            $wallet = CompanyWallet::where('company_id', $settlement->company_id)
                ->where('currency', 'NGN')
                ->lockForUpdate()
                ->first();

            if (!$wallet) {
                throw new \Exception("Wallet not found for company: {$settlement->company_id}");
            }

            // Credit the wallet
            $balanceBefore = $wallet->balance;
            $wallet->credit($settlement->amount);
            $wallet->save();

            echo "  Old Balance: ₦" . number_format($balanceBefore, 2) . "\n";
            echo "  New Balance: ₦" . number_format($wallet->balance, 2) . "\n";

            // Update transaction with settlement info
            $transaction->update([
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'settled_at' => now()->toDateTimeString(),
                    'settlement_note' => 'Manually processed via emergency script',
                ]),
            ]);

            // Mark settlement as completed
            DB::table('settlement_queue')
                ->where('id', $settlement->id)
                ->update([
                    'status' => 'completed',
                    'actual_settlement_date' => now(),
                    'settlement_note' => 'Manually processed - emergency settlement',
                ]);

            DB::commit();

            echo "  ✓ SUCCESS\n";
            $processed++;
            $totalAmount += $settlement->amount;

        } catch (\Exception $e) {
            DB::rollBack();

            // Mark as failed
            DB::table('settlement_queue')
                ->where('id', $settlement->id)
                ->update([
                    'status' => 'failed',
                    'settlement_note' => $e->getMessage(),
                ]);

            echo "  ✗ FAILED: {$e->getMessage()}\n";
            $failed++;
        }

        echo "\n";
    }

    echo "========================================\n";
    echo "Settlement Summary\n";
    echo "========================================\n";
    echo "Processed: {$processed}\n";
    echo "Failed: {$failed}\n";
    echo "Total Amount Settled: ₦" . number_format($totalAmount, 2) . "\n";
    echo "\n";

    if ($processed > 0) {
        echo "✓ Settlements processed successfully!\n";
        echo "  Check your company dashboard - balance should be updated\n";
    }

    echo "\n";

} catch (\Exception $e) {
    echo "✗ ERROR: {$e->getMessage()}\n";
    echo "\n";
    exit(1);
}
