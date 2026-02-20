<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    CHECK PENDING SETTLEMENTS AFTER MANUAL PROCESSING        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Check transactions table
echo "1. CHECKING TRANSACTIONS TABLE\n";
echo "------------------------------------------------------------\n";

$transactions = DB::table('transactions')
    ->where('transaction_type', 'va_deposit')
    ->where('status', 'success')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();

echo "Recent VA Deposit Transactions:\n\n";
foreach ($transactions as $txn) {
    echo "Transaction ID: {$txn->transaction_id}\n";
    echo "  Amount: ₦" . number_format($txn->amount, 2) . "\n";
    echo "  Net Amount: ₦" . number_format($txn->net_amount, 2) . "\n";
    echo "  Status: {$txn->status}\n";
    echo "  Settlement Status: {$txn->settlement_status}\n";
    echo "  Created: {$txn->created_at}\n";
    echo "  Updated: {$txn->updated_at}\n";
    echo "\n";
}

// Check settlement queue
echo "\n2. CHECKING SETTLEMENT QUEUE\n";
echo "------------------------------------------------------------\n";

$queueItems = DB::table('settlement_queue')
    ->orderBy('scheduled_date', 'desc')
    ->limit(10)
    ->get();

if ($queueItems->isEmpty()) {
    echo "✅ Settlement queue is EMPTY (good!)\n\n";
} else {
    echo "⚠️  Settlement queue still has items:\n\n";
    foreach ($queueItems as $item) {
        echo "Transaction ID: {$item->transaction_id}\n";
        echo "  Net Amount: ₦" . number_format($item->net_amount, 2) . "\n";
        echo "  Scheduled: {$item->scheduled_date}\n";
        echo "  Status: {$item->status}\n";
        echo "\n";
    }
}

// Check what the API would return
echo "\n3. SIMULATING API CALL (Yesterday Filter)\n";
echo "------------------------------------------------------------\n";

$now = \Carbon\Carbon::now('Africa/Lagos');
$startDate = $now->copy()->subDay()->startOfDay();
$endDate = $now->copy()->subDay()->endOfDay();

echo "Date Range: {$startDate} to {$endDate}\n\n";

$pendingYesterday = DB::table('transactions')
    ->where('transaction_type', 'va_deposit')
    ->where('status', 'success')
    ->where('settlement_status', 'unsettled')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->get();

echo "Pending Transactions (Yesterday): " . $pendingYesterday->count() . "\n";
if ($pendingYesterday->count() > 0) {
    echo "⚠️  PROBLEM: Still showing unsettled transactions!\n\n";
    foreach ($pendingYesterday as $txn) {
        echo "  - {$txn->transaction_id}: ₦{$txn->net_amount} (settlement_status: {$txn->settlement_status})\n";
    }
} else {
    echo "✅ No pending transactions for yesterday\n";
}

echo "\n\n4. SIMULATING API CALL (Today Filter)\n";
echo "------------------------------------------------------------\n";

$startDate = $now->copy()->startOfDay();
$endDate = $now;

echo "Date Range: {$startDate} to {$endDate}\n\n";

$pendingToday = DB::table('transactions')
    ->where('transaction_type', 'va_deposit')
    ->where('status', 'success')
    ->where('settlement_status', 'unsettled')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->get();

echo "Pending Transactions (Today): " . $pendingToday->count() . "\n";
if ($pendingToday->count() > 0) {
    echo "Found unsettled transactions:\n\n";
    foreach ($pendingToday as $txn) {
        echo "  - {$txn->transaction_id}: ₦{$txn->net_amount} (settlement_status: {$txn->settlement_status})\n";
    }
} else {
    echo "✅ No pending transactions for today\n";
}

echo "\n\n5. CHECKING COMPANY WALLETS\n";
echo "------------------------------------------------------------\n";

$wallets = DB::table('company_wallets')
    ->join('companies', 'company_wallets.company_id', '=', 'companies.id')
    ->select('companies.name', 'company_wallets.balance', 'company_wallets.updated_at')
    ->get();

foreach ($wallets as $wallet) {
    echo "Company: {$wallet->name}\n";
    echo "  Balance: ₦" . number_format($wallet->balance, 2) . "\n";
    echo "  Last Updated: {$wallet->updated_at}\n\n";
}

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    DIAGNOSIS COMPLETE                                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
