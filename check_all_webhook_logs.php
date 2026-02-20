<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING ALL WEBHOOK LOGS ===\n\n";

// Check total count
$total = DB::table('palmpay_webhooks')->count();
echo "Total webhook logs in database: $total\n\n";

if ($total > 0) {
    echo "=== RECENT WEBHOOK LOGS ===\n";
    $logs = DB::table('palmpay_webhooks')
        ->join('companies', 'palmpay_webhooks.company_id', '=', 'companies.id')
        ->join('users', 'companies.user_id', '=', 'users.id')
        ->select(
            'palmpay_webhooks.*',
            'users.name as company_name'
        )
        ->orderBy('palmpay_webhooks.created_at', 'desc')
        ->limit(5)
        ->get();
    
    foreach ($logs as $log) {
        echo "ID: {$log->id}\n";
        echo "Company: {$log->company_name} (ID: {$log->company_id})\n";
        echo "Event: {$log->event_type}\n";
        echo "Status: {$log->status}\n";
        echo "HTTP Status: {$log->http_status}\n";
        echo "Sent At: {$log->sent_at}\n";
        echo "---\n";
    }
} else {
    echo "❌ No webhook logs found in database\n";
    echo "This is why the page is empty!\n\n";
    echo "Webhook logs are created when:\n";
    echo "1. A webhook event is triggered (deposit, transfer, etc.)\n";
    echo "2. The system attempts to send the webhook to the company's webhook URL\n";
    echo "3. The attempt is logged regardless of success or failure\n\n";
    echo "To test:\n";
    echo "1. Make sure a company has a webhook URL configured\n";
    echo "2. Make a deposit or transfer\n";
    echo "3. Check if webhook logs are created\n";
}
