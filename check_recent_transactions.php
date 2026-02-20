<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING RECENT TRANSACTIONS ===\n\n";

// Get recent transactions (last 24 hours)
$transactions = DB::table('transactions')
    ->where('created_at', '>=', now()->subDay())
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

echo "Total transactions in last 24 hours: " . $transactions->count() . "\n\n";

if ($transactions->count() > 0) {
    echo "=== RECENT TRANSACTIONS ===\n";
    foreach ($transactions as $txn) {
        echo "\nTransaction ID: {$txn->transaction_id}\n";
        echo "Type: {$txn->type}\n";
        echo "Category: {$txn->category}\n";
        echo "Amount: ₦" . number_format($txn->amount, 2) . "\n";
        echo "Status: {$txn->status}\n";
        echo "Provider: {$txn->provider}\n";
        echo "Created: {$txn->created_at}\n";
        
        if ($txn->palmpay_reference) {
            echo "PalmPay Reference: {$txn->palmpay_reference}\n";
        }
        
        echo "---\n";
    }
} else {
    echo "ℹ️  No transactions found in the last 24 hours.\n";
}

// Check all transactions count
echo "\n=== TOTAL TRANSACTIONS ===\n";
$totalCount = DB::table('transactions')->count();
echo "Total transactions in database: {$totalCount}\n";

if ($totalCount > 0) {
    echo "\n=== LAST 5 TRANSACTIONS (ANY TIME) ===\n";
    $lastTransactions = DB::table('transactions')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    foreach ($lastTransactions as $txn) {
        echo "\nTransaction ID: {$txn->transaction_id}\n";
        echo "Type: {$txn->type}\n";
        echo "Amount: ₦" . number_format($txn->amount, 2) . "\n";
        echo "Status: {$txn->status}\n";
        echo "Created: {$txn->created_at}\n";
        echo "---\n";
    }
}
