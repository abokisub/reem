<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "Fix Net Amount for Debit Transactions\n";
echo "========================================\n\n";

echo "PROBLEM:\n";
echo "For debit transactions (withdrawals/transfers), net_amount was calculated as:\n";
echo "  net_amount = amount - fee\n";
echo "This is WRONG! It should be:\n";
echo "  net_amount = amount (what recipient receives)\n\n";

echo "EXAMPLE:\n";
echo "  Company withdraws ₦100\n";
echo "  Fee: ₦15\n";
echo "  Total deducted: ₦115\n";
echo "  OLD net_amount: ₦85 (WRONG!)\n";
echo "  NEW net_amount: ₦100 (CORRECT - what recipient receives)\n\n";

// Get all debit transactions with incorrect net_amount
$incorrectTransactions = Transaction::where('type', 'debit')
    ->whereRaw('net_amount != amount')
    ->get();

echo "Found " . $incorrectTransactions->count() . " debit transactions with incorrect net_amount\n\n";

if ($incorrectTransactions->count() === 0) {
    echo "✅ No transactions need fixing!\n";
    exit(0);
}

echo "Fixing transactions...\n";
echo str_repeat("-", 80) . "\n";

$fixed = 0;
foreach ($incorrectTransactions as $transaction) {
    $oldNetAmount = $transaction->net_amount;
    $newNetAmount = $transaction->amount;
    
    echo sprintf(
        "ID: %d | Ref: %s | Amount: ₦%.2f | Fee: ₦%.2f | Old Net: ₦%.2f → New Net: ₦%.2f\n",
        $transaction->id,
        $transaction->reference,
        $transaction->amount,
        $transaction->fee,
        $oldNetAmount,
        $newNetAmount
    );
    
    // Update net_amount
    DB::table('transactions')
        ->where('id', $transaction->id)
        ->update(['net_amount' => $newNetAmount]);
    
    $fixed++;
}

echo str_repeat("-", 80) . "\n";
echo "\n✅ Fixed $fixed transactions!\n\n";

echo "VERIFICATION:\n";
$remaining = Transaction::where('type', 'debit')
    ->whereRaw('net_amount != amount')
    ->count();

if ($remaining === 0) {
    echo "✅ All debit transactions now have correct net_amount!\n";
} else {
    echo "⚠️  Warning: $remaining transactions still have incorrect net_amount\n";
}

echo "\n✅ Done!\n";
