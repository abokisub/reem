<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking transfer transactions...\n\n";

// Get recent transfer transactions
$transfers = DB::table('transactions')
    ->where('company_id', 2) // Your company ID
    ->where('category', 'transfer')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

echo "Found " . $transfers->count() . " transfer transactions:\n\n";

foreach ($transfers as $transfer) {
    echo "ID: {$transfer->id}\n";
    echo "Reference: {$transfer->reference}\n";
    echo "Type: " . ($transfer->type ?? 'NULL') . "\n";
    echo "Category: {$transfer->category}\n";
    echo "Transaction Type: " . ($transfer->transaction_type ?? 'NULL') . "\n";
    echo "Amount: {$transfer->amount}\n";
    echo "Status: {$transfer->status}\n";
    echo "Created: {$transfer->created_at}\n";
    echo "---\n\n";
}

// Check what the AllHistoryUser query would return
echo "\nChecking AllHistoryUser query (debit transactions):\n";
$debitTransactions = DB::table('transactions')
    ->where('company_id', 2)
    ->where('type', 'debit')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get(['id', 'reference', 'type', 'category', 'transaction_type', 'amount', 'status']);

echo "Found " . $debitTransactions->count() . " debit transactions:\n\n";
foreach ($debitTransactions as $trans) {
    echo "ID: {$trans->id}, Ref: {$trans->reference}, Type: {$trans->type}, Category: {$trans->category}, TxType: " . ($trans->transaction_type ?? 'NULL') . "\n";
}

echo "\nDone!\n";
