<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING WEBHOOK STORAGE ===\n\n";

// Check palmpay_webhooks table
echo "1. PalmPay Webhooks Table:\n";
$palmpayWebhooks = DB::table('palmpay_webhooks')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "   Total records: " . DB::table('palmpay_webhooks')->count() . "\n";
if ($palmpayWebhooks->count() > 0) {
    echo "   Recent webhooks:\n";
    foreach ($palmpayWebhooks as $webhook) {
        echo "   - ID: {$webhook->id} | Event: {$webhook->event_type} | Status: {$webhook->status} | Created: {$webhook->created_at}\n";
    }
} else {
    echo "   ❌ No records found\n";
}
echo "\n";

// Check webhook_events table (if exists)
if (Schema::hasTable('webhook_events')) {
    echo "2. Webhook Events Table:\n";
    $webhookEvents = DB::table('webhook_events')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    echo "   Total records: " . DB::table('webhook_events')->count() . "\n";
    if ($webhookEvents->count() > 0) {
        echo "   Recent events:\n";
        foreach ($webhookEvents as $event) {
            echo "   - ID: {$event->id} | Type: {$event->event_type} | Direction: {$event->direction} | Created: {$event->created_at}\n";
        }
    } else {
        echo "   ❌ No records found\n";
    }
} else {
    echo "2. Webhook Events Table: ❌ Table does not exist\n";
}
echo "\n";

// Check company_webhook_logs table (if exists)
if (Schema::hasTable('company_webhook_logs')) {
    echo "3. Company Webhook Logs Table:\n";
    $companyLogs = DB::table('company_webhook_logs')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    echo "   Total records: " . DB::table('company_webhook_logs')->count() . "\n";
    if ($companyLogs->count() > 0) {
        echo "   Recent logs:\n";
        foreach ($companyLogs as $log) {
            echo "   - ID: {$log->id} | Event: {$log->event_type} | Status: {$log->status} | Created: {$log->created_at}\n";
        }
    } else {
        echo "   ❌ No records found\n";
    }
} else {
    echo "3. Company Webhook Logs Table: ❌ Table does not exist\n";
}
echo "\n";

echo "=== ADMIN WEBHOOK ENDPOINT CHECK ===\n\n";

// Check what the admin webhook endpoint is querying
echo "The admin webhook logs page queries: webhook_events table\n";
echo "The company webhook page queries: company_webhook_logs table\n";
echo "PalmPay incoming webhooks are stored in: palmpay_webhooks table\n\n";

echo "=== ISSUE IDENTIFIED ===\n\n";
echo "The admin webhook logs page is looking for data in 'webhook_events' table,\n";
echo "but PalmPay webhooks are stored in 'palmpay_webhooks' table.\n\n";
echo "These are two different systems:\n";
echo "1. palmpay_webhooks = Incoming webhooks FROM PalmPay\n";
echo "2. webhook_events = Outgoing webhooks TO your customers\n";
echo "3. company_webhook_logs = Outgoing webhook delivery logs\n\n";
