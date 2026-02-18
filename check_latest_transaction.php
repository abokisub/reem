<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING LATEST TRANSACTION ===\n\n";

// Check the transaction from the logs
$tx = DB::table('transactions')->where('transaction_id', 'txn_6995fdbf8c0ac44478')->first();

if ($tx) {
    echo "Transaction Found:\n";
    echo "  Transaction ID: {$tx->transaction_id}\n";
    echo "  Company ID: {$tx->company_id}\n";
    echo "  Amount: ₦{$tx->amount}\n";
    echo "  Fee: ₦{$tx->fee}\n";
    echo "  Net Amount: ₦{$tx->net_amount}\n";
    echo "  Total Amount: ₦{$tx->total_amount}\n";
    echo "  Type: {$tx->type}\n";
    echo "  Status: {$tx->status}\n";
    echo "  Channel: {$tx->channel}\n";
    echo "  Created At: {$tx->created_at}\n\n";
} else {
    echo "Transaction NOT found!\n\n";
}

// Check all transactions for company 2
echo "All Transactions for Company 2:\n";
$allTx = DB::table('transactions')
    ->where('company_id', 2)
    ->orderBy('created_at', 'desc')
    ->get();

echo "Total: " . $allTx->count() . " transactions\n\n";
foreach ($allTx as $t) {
    echo "  - {$t->transaction_id}: ₦{$t->amount} ({$t->status}) - {$t->created_at}\n";
}

echo "\n=== END CHECK ===\n";
