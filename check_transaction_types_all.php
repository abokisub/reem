<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking All Transaction Types\n";
echo "===============================\n\n";

// Get distinct types
$types = DB::table('transactions')
    ->select('type', DB::raw('count(*) as count'))
    ->groupBy('type')
    ->get();

echo "Transaction Types:\n";
foreach ($types as $type) {
    echo "  {$type->type}: {$type->count} transactions\n";
}

echo "\n";

// Get distinct transaction_type
$transactionTypes = DB::table('transactions')
    ->select('transaction_type', DB::raw('count(*) as count'))
    ->groupBy('transaction_type')
    ->get();

echo "Transaction Type Column:\n";
foreach ($transactionTypes as $type) {
    $typeValue = $type->transaction_type ?? 'NULL';
    echo "  {$typeValue}: {$type->count} transactions\n";
}

echo "\n";

// Get recent transactions
$recent = DB::table('transactions')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get(['id', 'reference', 'type', 'transaction_type', 'status', 'settlement_status', 'amount', 'description']);

echo "Recent 10 Transactions:\n";
foreach ($recent as $t) {
    echo "ID: {$t->id} | Ref: {$t->reference} | Type: {$t->type} | TxType: " . ($t->transaction_type ?? 'NULL') . " | Status: {$t->status} | Settlement: " . ($t->settlement_status ?? 'NULL') . "\n";
}
