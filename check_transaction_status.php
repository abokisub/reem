<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING TRANSACTION STATUSES ===\n\n";

$transactions = DB::table('transactions')
    ->where('company_id', 2)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "Company 2 Transactions:\n\n";
foreach ($transactions as $tx) {
    echo "Transaction: {$tx->transaction_id}\n";
    echo "  Amount: ₦{$tx->amount}\n";
    echo "  Status: {$tx->status}\n";
    echo "  Type: {$tx->type}\n";
    echo "  Category: {$tx->category}\n";
    echo "  Created: {$tx->created_at}\n";
    echo "\n";
}

echo "\n=== STATUS FIELD VALUES ===\n";
$statuses = DB::table('transactions')
    ->select('status', DB::raw('count(*) as count'))
    ->groupBy('status')
    ->get();

foreach ($statuses as $status) {
    echo "  {$status->status}: {$status->count} transactions\n";
}
