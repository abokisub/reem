<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFYING RA TRANSACTIONS PAGE DATA ===\n\n";

// Check transactions that should appear on RA Transactions page
$transactions = DB::table('transactions')
    ->whereIn('transaction_type', ['va_deposit', 'api_transfer', 'company_withdrawal', 'refund'])
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get(['transaction_id', 'transaction_type', 'amount', 'status', 'created_at']);

echo "Transactions visible on RA Transactions page:\n";
echo "Count: " . $transactions->count() . "\n\n";

foreach ($transactions as $txn) {
    echo "✅ {$txn->transaction_id}\n";
    echo "   Type: {$txn->transaction_type}\n";
    echo "   Amount: ₦" . number_format($txn->amount, 2) . "\n";
    echo "   Status: {$txn->status}\n";
    echo "   Date: {$txn->created_at}\n\n";
}

// Check if there are any transactions with NULL transaction_type
$nullTypeCount = DB::table('transactions')
    ->whereNull('transaction_type')
    ->count();

if ($nullTypeCount > 0) {
    echo "⚠️  WARNING: {$nullTypeCount} transactions still have NULL transaction_type\n";
} else {
    echo "✅ All transactions have transaction_type set\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";
