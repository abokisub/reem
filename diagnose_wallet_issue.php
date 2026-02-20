<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Diagnosing Wallet Page Issue...\n\n";

$companyId = 2; // Your company ID

echo "1. Checking all debit transactions:\n";
$debitTrans = DB::table('transactions')
    ->where('company_id', $companyId)
    ->where('type', 'debit')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get(['id', 'reference', 'type', 'category', 'transaction_type', 'status', 'amount', 'created_at']);

echo "Found " . $debitTrans->count() . " debit transactions:\n";
foreach ($debitTrans as $trans) {
    echo "  ID: {$trans->id}, Ref: {$trans->reference}, Status: '{$trans->status}', TxType: " . ($trans->transaction_type ?? 'NULL') . ", Amount: {$trans->amount}\n";
}

echo "\n2. Checking status values in database:\n";
$statusCounts = DB::table('transactions')
    ->where('company_id', $companyId)
    ->where('type', 'debit')
    ->select('status', DB::raw('COUNT(*) as count'))
    ->groupBy('status')
    ->get();

foreach ($statusCounts as $stat) {
    echo "  Status '{$stat->status}': {$stat->count} transactions\n";
}

echo "\n3. Testing AllHistoryUser query:\n";
$testQuery = DB::table('transactions')
    ->where('company_id', $companyId)
    ->where('type', 'debit')
    ->select(
        'description as message',
        'amount',
        'balance_before as oldbal',
        'balance_after as newbal',
        'created_at as Habukhan_date',
        'created_at as adex_date',
        'reference as transid',
        'transaction_type',
        DB::raw("CASE WHEN status = 'successful' THEN 1 WHEN status = 'failed' THEN 2 ELSE 0 END as plan_status"),
        DB::raw("'user' as role"),
        DB::raw("'transactions' as source")
    )
    ->orderBy('Habukhan_date', 'desc')
    ->limit(5)
    ->get();

echo "Query returned " . $testQuery->count() . " results:\n";
foreach ($testQuery as $result) {
    echo "  Ref: {$result->transid}, Status: {$result->plan_status}, TxType: " . ($result->transaction_type ?? 'NULL') . "\n";
}

echo "\n4. Checking webhook_events table:\n";
$webhookCount = DB::table('webhook_events')
    ->where('company_id', $companyId)
    ->count();
echo "Found {$webhookCount} webhook events for company {$companyId}\n";

if ($webhookCount > 0) {
    $recentWebhooks = DB::table('webhook_events')
        ->where('company_id', $companyId)
        ->orderBy('id', 'desc')
        ->limit(5)
        ->get(['id', 'event_type', 'status', 'created_at']);
    
    foreach ($recentWebhooks as $webhook) {
        echo "  ID: {$webhook->id}, Type: {$webhook->event_type}, Status: {$webhook->status}\n";
    }
}

echo "\nDone!\n";
