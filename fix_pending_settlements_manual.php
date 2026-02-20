<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Models\SettlementQueue;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Fix Pending Settlement Withdrawals\n";
echo "========================================\n\n";

echo "NOTE: Settlement withdrawals are AUTOMATICALLY marked as 'settled' when created.\n";
echo "This script fixes OLD settlement withdrawals that were created before the fix.\n\n";

// Get the two pending settlement withdrawal transactions
$references = ['REF6698239144585538', 'REF6698379939769138'];

foreach ($references as $reference) {
    echo "Checking transaction: $reference\n";
    echo str_repeat("-", 60) . "\n";
    
    $transaction = Transaction::where('reference', $reference)->first();
    
    if (!$transaction) {
        echo "❌ Transaction not found!\n\n";
        continue;
    }
    
    echo "Current Status:\n";
    echo "  - Transaction Type: {$transaction->transaction_type}\n";
    echo "  - Transaction Status: {$transaction->status}\n";
    echo "  - Settlement Status: {$transaction->settlement_status}\n";
    echo "  - Amount: ₦{$transaction->amount}\n";
    
    // Only fix if it's a settlement_withdrawal or transfer
    if (in_array($transaction->transaction_type, ['settlement_withdrawal', 'transfer']) && 
        $transaction->status === 'successful' && 
        $transaction->settlement_status !== 'settled') {
        
        echo "\n🔧 Fixing settlement status...\n";
        
        // Update transaction settlement_status
        $transaction->settlement_status = 'settled';
        $transaction->save();
        
        echo "✅ Transaction settlement_status updated to 'settled'\n";
    } else {
        echo "\nℹ️  No fix needed\n";
        if ($transaction->settlement_status === 'settled') {
            echo "   Reason: Already settled\n";
        } elseif ($transaction->transaction_type === 'va_deposit') {
            echo "   Reason: VA deposits are settled through Pending Settlements page\n";
        }
    }
    
    echo "\n";
}

echo "========================================\n";
echo "Verification\n";
echo "========================================\n\n";

// Check all pending settlements
$pendingSettlements = Transaction::where('settlement_status', 'unsettled')
    ->where('status', 'successful')
    ->whereIn('transaction_type', ['settlement_withdrawal', 'transfer'])
    ->get();

echo "Remaining Pending Settlements: " . $pendingSettlements->count() . "\n\n";

if ($pendingSettlements->count() > 0) {
    echo "Details:\n";
    foreach ($pendingSettlements as $txn) {
        echo "  - {$txn->reference}: {$txn->transaction_type}, ₦{$txn->amount}, Status: {$txn->status}, Settlement: {$txn->settlement_status}\n";
    }
} else {
    echo "✅ No pending settlements found!\n";
}

echo "\n✅ Done!\n";
