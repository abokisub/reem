<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Fix Transfer Balance Issue\n";
echo "========================================\n\n";

// Get company wallet for company ID 2
$wallet = \App\Models\CompanyWallet::where('company_id', 2)->first();

if (!$wallet) {
    echo "❌ Wallet not found for company ID 2\n";
    exit(1);
}

echo "Current Wallet State:\n";
echo "Balance: ₦" . number_format($wallet->balance, 2) . "\n";
echo "Ledger Balance: ₦" . number_format($wallet->ledger_balance, 2) . "\n";
echo "Pending Balance: ₦" . number_format($wallet->pending_balance, 2) . "\n\n";

// Find any pending transfer_out transactions that might be stuck
$pendingTransfers = \App\Models\Transaction::where('company_id', 2)
    ->where('category', 'transfer_out')
    ->where('status', 'pending')
    ->get();

if ($pendingTransfers->count() > 0) {
    echo "Found {$pendingTransfers->count()} pending transfer(s):\n";
    echo "-----------------------------------\n";
    
    foreach ($pendingTransfers as $txn) {
        echo "Reference: {$txn->reference}\n";
        echo "Amount: ₦" . number_format($txn->amount, 2) . "\n";
        echo "Fee: ₦" . number_format($txn->fee, 2) . "\n";
        echo "Total Deducted: ₦" . number_format($txn->total_amount, 2) . "\n";
        echo "Created: {$txn->created_at}\n";
        echo "Balance Before: ₦" . number_format($txn->balance_before, 2) . "\n";
        echo "Balance After: ₦" . number_format($txn->balance_after, 2) . "\n";
        
        // Check if this transaction is older than 5 minutes (likely failed)
        $age = now()->diffInMinutes($txn->created_at);
        
        if ($age > 5) {
            echo "\n⚠️  This transaction is {$age} minutes old and still pending.\n";
            echo "Would you like to refund it? (This will mark it as failed and credit back the wallet)\n";
            echo "Type 'yes' to refund: ";
            
            $handle = fopen("php://stdin", "r");
            $line = trim(fgets($handle));
            
            if (strtolower($line) === 'yes') {
                DB::transaction(function () use ($txn, $wallet) {
                    // Credit back the wallet
                    $wallet->credit($txn->total_amount);
                    
                    // Update transaction status
                    $txn->update([
                        'status' => 'failed',
                        'error_message' => 'Auto-refunded: Transaction stuck in pending state',
                        'balance_after' => $wallet->balance,
                        'updated_at' => now()
                    ]);
                    
                    // Update message table
                    DB::table('message')->where('transid', $txn->reference)->update([
                        'plan_status' => 0,
                        'message' => 'Transfer FAILED and Refunded. Reason: Transaction stuck in pending state',
                        'newbal' => $wallet->balance
                    ]);
                });
                
                echo "✅ Refunded ₦" . number_format($txn->total_amount, 2) . "\n";
                echo "New Balance: ₦" . number_format($wallet->fresh()->balance, 2) . "\n";
            } else {
                echo "Skipped.\n";
            }
        }
        
        echo "-----------------------------------\n\n";
    }
} else {
    echo "✅ No pending transfer_out transactions found.\n\n";
}

// Calculate expected balance from transaction history
echo "Verifying Balance Integrity:\n";
echo "-----------------------------------\n";

$allTransactions = \App\Models\Transaction::where('company_id', 2)
    ->orderBy('created_at', 'asc')
    ->get();

$calculatedBalance = 0;

foreach ($allTransactions as $txn) {
    if ($txn->type === 'credit') {
        $calculatedBalance += $txn->amount - $txn->fee;
    } elseif ($txn->type === 'debit') {
        if ($txn->status === 'success' || $txn->status === 'pending') {
            $calculatedBalance -= ($txn->amount + $txn->fee);
        }
        // Failed transactions should have been refunded, so we don't deduct them
    }
}

echo "Calculated Balance from History: ₦" . number_format($calculatedBalance, 2) . "\n";
echo "Current Wallet Balance: ₦" . number_format($wallet->fresh()->balance, 2) . "\n";

$difference = abs($calculatedBalance - $wallet->fresh()->balance);

if ($difference > 0.01) {
    echo "\n⚠️  MISMATCH DETECTED: ₦" . number_format($difference, 2) . "\n";
    echo "This indicates a balance integrity issue.\n";
} else {
    echo "\n✅ Balance is correct!\n";
}

echo "\n========================================\n";
echo "Fix Complete\n";
echo "========================================\n";
