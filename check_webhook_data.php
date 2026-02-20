<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING WEBHOOK DATA ===\n\n";

// Check incoming webhooks (from PalmPay)
echo "INCOMING WEBHOOKS (palmpay_webhooks table):\n";
echo str_repeat("=", 80) . "\n";
$incoming = DB::table('palmpay_webhooks')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($incoming->isEmpty()) {
    echo "No incoming webhooks found\n\n";
} else {
    foreach ($incoming as $webhook) {
        echo "ID: {$webhook->id}\n";
        echo "Event: {$webhook->event_type}\n";
        echo "Order No: {$webhook->order_no}\n";
        echo "Amount: {$webhook->order_amount}\n";
        echo "Created: {$webhook->created_at}\n";
        echo str_repeat("-", 80) . "\n";
    }
}

// Check outgoing webhooks (to companies)
echo "\nOUTGOING WEBHOOKS (webhook_logs table):\n";
echo str_repeat("=", 80) . "\n";
$outgoing = DB::table('webhook_logs')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($outgoing->isEmpty()) {
    echo "No outgoing webhooks found\n\n";
} else {
    foreach ($outgoing as $webhook) {
        echo "ID: {$webhook->id}\n";
        echo "Company ID: {$webhook->company_id}\n";
        echo "Event: {$webhook->event}\n";
        echo "URL: {$webhook->url}\n";
        echo "Status: {$webhook->status}\n";
        echo "Created: {$webhook->created_at}\n";
        echo str_repeat("-", 80) . "\n";
    }
}

// Test the API endpoint
echo "\nTESTING API ENDPOINT:\n";
echo str_repeat("=", 80) . "\n";

$webhooks = DB::select("
    SELECT 
        'incoming' as direction,
        id,
        event_type as event,
        order_no as reference,
        order_amount as amount,
        created_at,
        NULL as url,
        NULL as status
    FROM palmpay_webhooks
    
    UNION ALL
    
    SELECT 
        'outgoing' as direction,
        id,
        event,
        reference,
        NULL as amount,
        created_at,
        url,
        status
    FROM webhook_logs
    
    ORDER BY created_at DESC
    LIMIT 10
");

echo "Total webhooks (combined): " . count($webhooks) . "\n\n";

foreach ($webhooks as $webhook) {
    echo "Direction: {$webhook->direction}\n";
    echo "Event: {$webhook->event}\n";
    echo "Reference: {$webhook->reference}\n";
    if ($webhook->direction === 'incoming') {
        echo "Amount: {$webhook->amount}\n";
    } else {
        echo "URL: {$webhook->url}\n";
        echo "Status: {$webhook->status}\n";
    }
    echo "Created: {$webhook->created_at}\n";
    echo str_repeat("-", 80) . "\n";
}
