<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING WEBHOOK LOGS TABLE ===\n\n";

// Check if table exists
try {
    $tableExists = DB::select("SHOW TABLES LIKE 'webhook_logs'");
    if (empty($tableExists)) {
        echo "❌ webhook_logs table does NOT exist!\n";
        exit(1);
    }
    echo "✅ webhook_logs table exists\n\n";
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "\n";
    exit(1);
}

// Check table structure
echo "=== TABLE STRUCTURE ===\n";
try {
    $columns = DB::select("DESCRIBE webhook_logs");
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type})\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Count total records
echo "=== RECORD COUNT ===\n";
try {
    $count = DB::table('webhook_logs')->count();
    echo "Total webhook logs: $count\n\n";
    
    if ($count > 0) {
        echo "=== SAMPLE RECORDS (Latest 5) ===\n";
        $logs = DB::table('webhook_logs')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        foreach ($logs as $log) {
            echo "ID: {$log->id}\n";
            echo "Company ID: {$log->company_id}\n";
            echo "Event Type: {$log->event_type}\n";
            echo "Status: {$log->status}\n";
            echo "Created: {$log->created_at}\n";
            echo "---\n";
        }
    } else {
        echo "ℹ️  No webhook logs found in database.\n";
        echo "This is normal if:\n";
        echo "  - No webhooks have been received from PalmPay yet\n";
        echo "  - Webhook logs were cleared\n";
        echo "  - PalmPay hasn't sent any notifications\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== CHECKING WEBHOOK ENDPOINT ===\n";
echo "Your webhook URL should be: https://app.pointwave.ng/api/v1/palmpay/webhook\n";
echo "Make sure this is configured in your PalmPay merchant dashboard.\n";
