<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Carbon\Carbon;

echo "=== DEBUGGING PENDING SETTLEMENTS ===\n\n";

// Set Nigerian timezone
date_default_timezone_set('Africa/Lagos');
$now = Carbon::now('Africa/Lagos');

echo "Current Nigerian Time: " . $now->toDateTimeString() . "\n\n";

// Check for TODAY
echo "--- TODAY FILTER ---\n";
$startDate = $now->copy()->startOfDay();
$endDate = $now;

echo "Start Date: " . $startDate->toDateTimeString() . "\n";
echo "End Date: " . $endDate->toDateTimeString() . "\n\n";

$todayTransactions = DB::table('transactions')
    ->where('transaction_type', 'va_deposit')
    ->where('status', 'success')
    ->where('settlement_status', 'unsettled')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->get();

echo "Found " . $todayTransactions->count() . " transactions for TODAY\n";

if ($todayTransactions->count() > 0) {
    echo "\nSample transactions:\n";
    foreach ($todayTransactions->take(5) as $tx) {
        echo "  ID: {$tx->id}, Reference: {$tx->reference}, Amount: {$tx->amount}, Created: {$tx->created_at}\n";
    }
}

echo "\n--- YESTERDAY FILTER ---\n";
$startDate = $now->copy()->subDay()->startOfDay();
$endDate = $now->copy()->subDay()->endOfDay();

echo "Start Date: " . $startDate->toDateTimeString() . "\n";
echo "End Date: " . $endDate->toDateTimeString() . "\n\n";

$yesterdayTransactions = DB::table('transactions')
    ->where('transaction_type', 'va_deposit')
    ->where('status', 'success')
    ->where('settlement_status', 'unsettled')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->get();

echo "Found " . $yesterdayTransactions->count() . " transactions for YESTERDAY\n";

if ($yesterdayTransactions->count() > 0) {
    echo "\nSample transactions:\n";
    foreach ($yesterdayTransactions->take(5) as $tx) {
        echo "  ID: {$tx->id}, Reference: {$tx->reference}, Amount: {$tx->amount}, Created: {$tx->created_at}\n";
    }
}

echo "\n--- ALL UNSETTLED VA DEPOSITS ---\n";
$allUnsettled = DB::table('transactions')
    ->where('transaction_type', 'va_deposit')
    ->where('status', 'success')
    ->where('settlement_status', 'unsettled')
    ->orderBy('created_at', 'desc')
    ->get();

echo "Total unsettled VA deposits: " . $allUnsettled->count() . "\n";

if ($allUnsettled->count() > 0) {
    echo "\nAll unsettled transactions:\n";
    foreach ($allUnsettled as $tx) {
        echo "  ID: {$tx->id}, Reference: {$tx->reference}, Amount: {$tx->amount}, Created: {$tx->created_at}\n";
    }
}

echo "\n=== END DEBUG ===\n";
