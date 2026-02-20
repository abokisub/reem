<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "CHECKING SETTLEMENT STATUS\n\n";

// Check recent transactions
$transactions = DB::table('transactions')
    ->where('transaction_type', 'va_deposit')
    ->where('status', 'success')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get(['transaction_id', 'amount', 'net_amount', 'settlement_status', 'created_at', 'updated_at']);

echo "Recent VA Deposits:\n";
foreach ($transactions as $txn) {
    echo "- {$txn->transaction_id}: ₦{$txn->net_amount} | Status: {$txn->settlement_status} | Created: {$txn->created_at}\n";
}

echo "\n\nCounting by settlement_status:\n";
$counts = DB::table('transactions')
    ->where('transaction_type', 'va_deposit')
    ->where('status', 'success')
    ->select('settlement_status', DB::raw('COUNT(*) as count'), DB::raw('SUM(net_amount) as total'))
    ->groupBy('settlement_status')
    ->get();

foreach ($counts as $count) {
    echo "- {$count->settlement_status}: {$count->count} transactions, ₦" . number_format($count->total, 2) . "\n";
}

echo "\n\nCompany Wallets:\n";
$wallets = DB::table('company_wallets')
    ->join('companies', 'company_wallets.company_id', '=', 'companies.id')
    ->select('companies.name', 'company_wallets.balance')
    ->get();

foreach ($wallets as $wallet) {
    echo "- {$wallet->name}: ₦" . number_format($wallet->balance, 2) . "\n";
}
