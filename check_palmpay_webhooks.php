<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== CHECKING PALMPAY_WEBHOOKS TABLE ===\n\n";

// Check if table exists
if (!Schema::hasTable('palmpay_webhooks')) {
    echo "❌ palmpay_webhooks table does NOT exist\n";
    exit(1);
}

echo "✅ palmpay_webhooks table exists\n\n";

// Get table structure
echo "=== TABLE STRUCTURE ===\n";
$columns = DB::select("DESCRIBE palmpay_webhooks");
foreach ($columns as $column) {
    echo "- {$column->Field} ({$column->Type})\n";
}

// Count records
echo "\n=== RECORD COUNT ===\n";
$count = DB::table('palmpay_webhooks')->count();
echo "Total palmpay webhooks: {$count}\n\n";

if ($count > 0) {
    echo "=== RECENT WEBHOOKS (Last 10) ===\n";
    $webhooks = DB::table('palmpay_webhooks')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    foreach ($webhooks as $webhook) {
        echo "\nWebhook ID: {$webhook->id}\n";
        echo "Event Type: {$webhook->event_type}\n";
        echo "Status: {$webhook->status}\n";
        echo "Processed: " . ($webhook->processed ? 'Yes' : 'No') . "\n";
        echo "Created: {$webhook->created_at}\n";
        
        if ($webhook->transaction_id) {
            echo "Transaction ID: {$webhook->transaction_id}\n";
        }
        
        if ($webhook->palmpay_reference) {
            echo "PalmPay Reference: {$webhook->palmpay_reference}\n";
        }
        
        echo "---\n";
    }
} else {
    echo "ℹ️  No webhooks found in palmpay_webhooks table.\n";
    echo "This means PalmPay hasn't sent any webhook notifications yet.\n";
}
