<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING TRANSACTION TYPE ISSUE ===\n\n";

// Find the recent transaction
$transaction = DB::table('transactions')
    ->where('transaction_id', 'txn_699861639a0ca24142')
    ->first();

if ($transaction) {
    echo "✅ Transaction Found\n";
    echo "Transaction ID: {$transaction->transaction_id}\n";
    echo "Reference: {$transaction->reference}\n";
    echo "Amount: ₦" . number_format($transaction->amount, 2) . "\n";
    echo "Category: " . ($transaction->category ?? 'NULL') . "\n";
    echo "Transaction Type: " . ($transaction->transaction_type ?? 'NULL') . " ⚠️\n";
    echo "Status: {$transaction->status}\n";
    echo "Created: {$transaction->created_at}\n\n";
    
    if (is_null($transaction->transaction_type)) {
        echo "❌ PROBLEM IDENTIFIED:\n";
        echo "   transaction_type is NULL\n";
        echo "   RA Transactions filters require transaction_type to be one of:\n";
        echo "   - va_deposit\n";
        echo "   - api_transfer\n";
        echo "   - company_withdrawal\n";
        echo "   - refund\n\n";
        echo "   This is why the transaction is not showing on RA Transactions page!\n\n";
    }
} else {
    echo "❌ Transaction NOT found\n";
}

// Check all transactions without transaction_type
echo "=== CHECKING ALL TRANSACTIONS WITHOUT transaction_type ===\n\n";
$nullTypeCount = DB::table('transactions')
    ->whereNull('transaction_type')
    ->count();

echo "Total transactions with NULL transaction_type: {$nullTypeCount}\n\n";

if ($nullTypeCount > 0) {
    echo "Recent transactions with NULL transaction_type:\n";
    $recent = DB::table('transactions')
        ->whereNull('transaction_type')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get(['transaction_id', 'category', 'amount', 'status', 'created_at']);
    
    foreach ($recent as $txn) {
        echo "  - {$txn->transaction_id} | Category: {$txn->category} | Amount: ₦" . number_format($txn->amount, 2) . " | {$txn->created_at}\n";
    }
}
