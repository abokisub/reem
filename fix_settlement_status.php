<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Fixing Settlement Transaction Status\n";
echo "====================================\n\n";

// Update all settlement_withdrawal transactions to have settlement_status = 'settled'
$updated = DB::table('transactions')
    ->where('transaction_type', 'settlement_withdrawal')
    ->update(['settlement_status' => 'settled']);

echo "Updated {$updated} settlement_withdrawal transactions to 'settled' status\n\n";

// Verify the update
$settlements = DB::table('transactions')
    ->where('transaction_type', 'settlement_withdrawal')
    ->get(['id', 'reference', 'settlement_status', 'status', 'amount']);

echo "Current Settlement Transactions:\n";
foreach ($settlements as $s) {
    echo "  ID: {$s->id} | Ref: {$s->reference} | Status: {$s->status} | Settlement: {$s->settlement_status} | Amount: {$s->amount}\n";
}

echo "\n✅ Done!\n";
