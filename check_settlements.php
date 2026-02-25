<?php

/**
 * Check Settlement Queue and Recent Transactions
 * 
 * Usage: php check_settlements.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Settlement Queue Checker ===\n\n";

// Check settlement_queue table
echo "=== Settlement Queue (Today and Future) ===\n";
$settlements = DB::table('settlement_queue')
    ->where('scheduled_settlement_date', '>=', date('Y-m-d'))
    ->orderBy('created_at', 'desc')
    ->get();

if ($settlements->count() > 0) {
    foreach ($settlements as $settlement) {
        echo "ID: {$settlement->id} | Company: {$settlement->company_id} | Amount: ₦{$settlement->amount} | Status: {$settlement->status} | Scheduled: {$settlement->scheduled_settlement_date}\n";
    }
} else {
    echo "No settlements in queue for today\n";
}

echo "\n=== Recent VA Deposits (Last 24 hours) ===\n";
$transactions = DB::table('transactions')
    ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-24 hours')))
    ->where('type', 'credit')
    ->where('status', 'successful')
    ->orderBy('created_at', 'desc')
    ->get(['id', 'transaction_id', 'reference', 'company_id', 'amount', 'fee', 'net_amount', 'transaction_type', 'category', 'settlement_status', 'created_at']);

if ($transactions->count() > 0) {
    foreach ($transactions as $tx) {
        echo "ID: {$tx->id} | Ref: {$tx->reference} | Company: {$tx->company_id} | Amount: ₦{$tx->amount} | Type: {$tx->transaction_type} | Category: {$tx->category} | Settlement: {$tx->settlement_status}\n";
    }
} else {
    echo "No recent deposits found\n";
}

echo "\n=== All Transaction Types (Last 24 hours) ===\n";
$types = DB::table('transactions')
    ->select('transaction_type', 'category', DB::raw('COUNT(*) as count'))
    ->where('created_at', '>=', date('Y-m-d H:i:s', strtotime('-24 hours')))
    ->groupBy('transaction_type', 'category')
    ->get();

foreach ($types as $type) {
    echo "Type: {$type->transaction_type} | Category: {$type->category} | Count: {$type->count}\n";
}

echo "\n";
