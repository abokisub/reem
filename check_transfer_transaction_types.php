<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING TRANSFER TRANSACTION TYPES ===\n\n";

// Check recent transfer transactions
$transfers = DB::table('transactions')
    ->where('company_id', 2)
    ->where('category', 'transfer_out')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get(['reference', 'transaction_type', 'category', 'type', 'amount', 'status', 'created_at']);

echo "Recent Transfer Transactions (category = transfer_out):\n";
echo str_repeat("=", 80) . "\n";
foreach ($transfers as $t) {
    echo "Reference: {$t->reference}\n";
    echo "  transaction_type: " . ($t->transaction_type ?? 'NULL') . "\n";
    echo "  category: {$t->category}\n";
    echo "  type: {$t->type}\n";
    echo "  amount: {$t->amount}\n";
    echo "  status: {$t->status}\n";
    echo "  created: {$t->created_at}\n\n";
}

echo "\n=== WHAT RA TRANSACTIONS LOOKS FOR ===\n";
echo "The AllRATransactions endpoint filters for these transaction_type values:\n";
echo "  - va_deposit\n";
echo "  - api_transfer\n";
echo "  - company_withdrawal\n";
echo "  - refund\n\n";

echo "=== ISSUE IDENTIFIED ===\n";
echo "Transfers are being created with transaction_type = 'transfer' or 'settlement_withdrawal'\n";
echo "But RA Transactions page only shows: va_deposit, api_transfer, company_withdrawal, refund\n";
echo "This is why transfers don't show up for companies!\n\n";
