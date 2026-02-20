<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Carbon\Carbon;

echo "=== CHECKING SETTLEMENT QUEUE ===\n\n";

// Check if settlement_queue table exists
if (!\Schema::hasTable('settlement_queue')) {
    echo "❌ settlement_queue table does NOT exist\n";
    exit(1);
}

echo "✅ settlement_queue table exists\n\n";

// Get all records from settlement_queue
$allQueue = DB::table('settlement_queue')
    ->orderBy('created_at', 'desc')
    ->get();

echo "Total records in settlement_queue: " . $allQueue->count() . "\n\n";

if ($allQueue->count() > 0) {
    echo "--- ALL SETTLEMENT QUEUE RECORDS ---\n";
    foreach ($allQueue as $queue) {
        echo "ID: {$queue->id}\n";
        echo "  Company ID: {$queue->company_id}\n";
        echo "  Transaction ID: " . ($queue->transaction_id ?? 'NULL') . "\n";
        echo "  Amount: ₦{$queue->amount}\n";
        echo "  Status: {$queue->status}\n";
        echo "  Scheduled For: " . ($queue->scheduled_for ?? 'NULL') . "\n";
        echo "  Created: {$queue->created_at}\n";
        echo "  Updated: " . ($queue->updated_at ?? 'NULL') . "\n";
        echo "\n";
    }
}

// Check pending settlements by company
echo "\n--- PENDING SETTLEMENTS BY COMPANY ---\n";
$pendingByCompany = DB::table('settlement_queue')
    ->join('companies', 'settlement_queue.company_id', '=', 'companies.id')
    ->where('settlement_queue.status', 'pending')
    ->select(
        'settlement_queue.company_id',
        'companies.name as company_name',
        DB::raw('COUNT(*) as count'),
        DB::raw('SUM(settlement_queue.amount) as total_amount')
    )
    ->groupBy('settlement_queue.company_id', 'companies.name')
    ->get();

if ($pendingByCompany->count() > 0) {
    foreach ($pendingByCompany as $company) {
        echo "Company: {$company->company_name} (ID: {$company->company_id})\n";
        echo "  Pending Count: {$company->count}\n";
        echo "  Total Amount: ₦{$company->total_amount}\n";
        echo "\n";
    }
} else {
    echo "No pending settlements found\n";
}

echo "\n=== END CHECK ===\n";
