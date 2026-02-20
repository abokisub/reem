<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING PALMPAY_WEBHOOKS TABLE ===\n\n";

try {
    // Check if table exists
    $tableExists = DB::select("SHOW TABLES LIKE 'palmpay_webhooks'");
    
    if (empty($tableExists)) {
        echo "❌ ERROR: palmpay_webhooks table does NOT exist!\n";
        echo "This is why the webhook logs page is empty.\n\n";
        echo "SOLUTION: You need to create the palmpay_webhooks table.\n";
        exit;
    }
    
    echo "✅ palmpay_webhooks table exists\n\n";
    
    // Check table structure
    echo "Table Structure:\n";
    $columns = DB::select("DESCRIBE palmpay_webhooks");
    foreach ($columns as $column) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }
    echo "\n";
    
    // Count records
    $count = DB::table('palmpay_webhooks')->count();
    echo "Total records: $count\n\n";
    
    if ($count > 0) {
        echo "Sample records:\n";
        $samples = DB::table('palmpay_webhooks')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        foreach ($samples as $sample) {
            echo "  ID: {$sample->id}\n";
            echo "  Event Type: {$sample->event_type}\n";
            echo "  Status: {$sample->status}\n";
            echo "  Created: {$sample->created_at}\n";
            echo "  ---\n";
        }
    } else {
        echo "No webhook records found in database.\n";
        echo "This is normal if no deposits have been made yet.\n";
    }
    
    // Test the actual API query
    echo "\n=== TESTING ACTUAL API QUERY ===\n\n";
    
    $logs = DB::table('palmpay_webhooks')
        ->leftJoin('transactions', 'palmpay_webhooks.transaction_id', '=', 'transactions.id')
        ->leftJoin('companies', 'transactions.company_id', '=', 'companies.id')
        ->select(
            'palmpay_webhooks.*',
            'companies.name as company_name',
            'transactions.reference as transaction_ref',
            'transactions.amount as transaction_amount',
            'palmpay_webhooks.created_at as sent_at'
        )
        ->orderBy('palmpay_webhooks.created_at', 'desc')
        ->paginate(50);
    
    echo "Pagination result:\n";
    echo "  Total: {$logs->total()}\n";
    echo "  Per Page: {$logs->perPage()}\n";
    echo "  Current Page: {$logs->currentPage()}\n";
    echo "  Data count: " . count($logs->items()) . "\n\n";
    
    echo "JSON structure:\n";
    echo json_encode([
        'status' => 'success',
        'webhook_logs' => $logs
    ], JSON_PRETTY_PRINT);
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
