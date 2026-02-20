<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Carbon\Carbon;

echo "=== CHECKING ALL TRANSACTIONS ===\n\n";

// Check recent transactions
echo "--- RECENT TRANSACTIONS (Last 24 hours) ---\n";
$now = Carbon::now('Africa/Lagos');
$yesterday = $now->copy()->subDay();

$recentTransactions = DB::table('transactions')
    ->where('created_at', '>=', $yesterday)
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

echo "Found " . $recentTransactions->count() . " transactions in last 24 hours\n\n";

if ($recentTransactions->count() > 0) {
    foreach ($recentTransactions as $tx) {
        echo "ID: {$tx->id}\n";
        echo "  Type: {$tx->transaction_type}\n";
        echo "  Status: {$tx->status}\n";
        echo "  Settlement Status: " . ($tx->settlement_status ?? 'NULL') . "\n";
        echo "  Amount: {$tx->amount}\n";
        echo "  Created: {$tx->created_at}\n";
        echo "  Reference: {$tx->reference}\n";
        echo "\n";
    }
}

echo "\n--- CHECKING TRANSACTION_TYPE VALUES ---\n";
$types = DB::table('transactions')
    ->select('transaction_type', DB::raw('COUNT(*) as count'))
    ->groupBy('transaction_type')
    ->get();

echo "Transaction types in database:\n";
foreach ($types as $type) {
    echo "  {$type->transaction_type}: {$type->count} transactions\n";
}

echo "\n--- CHECKING SETTLEMENT_STATUS VALUES ---\n";
$statuses = DB::table('transactions')
    ->select('settlement_status', DB::raw('COUNT(*) as count'))
    ->groupBy('settlement_status')
    ->get();

echo "Settlement statuses in database:\n";
foreach ($statuses as $status) {
    $statusValue = $status->settlement_status ?? 'NULL';
    echo "  {$statusValue}: {$status->count} transactions\n";
}

echo "\n--- CHECKING FOR PENDING SETTLEMENTS (ANY TYPE) ---\n";
$pending = DB::table('transactions')
    ->where('status', 'success')
    ->where(function($query) {
        $query->where('settlement_status', 'unsettled')
              ->orWhereNull('settlement_status');
    })
    ->where('created_at', '>=', $yesterday)
    ->get();

echo "Found " . $pending->count() . " potentially pending transactions\n";
if ($pending->count() > 0) {
    foreach ($pending->take(10) as $tx) {
        echo "  ID: {$tx->id}, Type: {$tx->transaction_type}, Status: {$tx->status}, Settlement: " . ($tx->settlement_status ?? 'NULL') . "\n";
    }
}

echo "\n=== END CHECK ===\n";
