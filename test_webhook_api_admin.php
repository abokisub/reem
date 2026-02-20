<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TESTING ADMIN WEBHOOK API ===\n\n";

// Get admin user
$admin = DB::table('users')->where('type', 'ADMIN')->first();

if (!$admin) {
    echo "No admin user found!\n";
    exit(1);
}

echo "Admin User ID: {$admin->id}\n";
echo "Admin Email: {$admin->email}\n\n";

// Simulate the API request
echo "Simulating API request: /api/secure/webhooks?id={$admin->id}&page=1&limit=20\n\n";

try {
    // Test the query directly
    $incomingQuery = DB::table('palmpay_webhooks')
        ->leftJoin('transactions', 'palmpay_webhooks.transaction_id', '=', 'transactions.id')
        ->leftJoin('companies', 'transactions.company_id', '=', 'companies.id')
        ->leftJoin('users', 'companies.user_id', '=', 'users.id')
        ->select(
            'palmpay_webhooks.id',
            DB::raw("'incoming' as direction"),
            'palmpay_webhooks.event_type',
            'palmpay_webhooks.status',
            'palmpay_webhooks.created_at',
            DB::raw("'N/A' as webhook_url"),
            DB::raw("'N/A' as http_status"),
            DB::raw("1 as attempt_number"),
            'users.name as company_name'
        );

    $outgoingQuery = DB::table('webhook_logs')
        ->leftJoin('companies', 'webhook_logs.company_id', '=', 'companies.id')
        ->select(
            'webhook_logs.id',
            DB::raw("'outgoing' as direction"),
            'webhook_logs.event_type',
            'webhook_logs.status',
            'webhook_logs.created_at',
            'webhook_logs.webhook_url',
            DB::raw("CAST(webhook_logs.http_status AS CHAR) as http_status"),
            'webhook_logs.attempt_number',
            'companies.name as company_name'
        );

    // Combine queries with UNION
    $combinedQuery = $incomingQuery->unionAll($outgoingQuery);
    
    // Get results
    $results = DB::table(DB::raw("({$combinedQuery->toSql()}) as combined_webhooks"))
        ->mergeBindings($combinedQuery)
        ->orderBy('created_at', 'desc')
        ->limit(20)
        ->get();

    echo "Total webhooks found: " . count($results) . "\n\n";

    if (count($results) > 0) {
        echo "Sample webhooks:\n";
        echo str_repeat("=", 80) . "\n";
        foreach ($results as $webhook) {
            echo "ID: {$webhook->id}\n";
            echo "Direction: {$webhook->direction}\n";
            echo "Event Type: {$webhook->event_type}\n";
            echo "Status: {$webhook->status}\n";
            echo "Company: " . ($webhook->company_name ?? 'N/A') . "\n";
            echo "Webhook URL: {$webhook->webhook_url}\n";
            echo "HTTP Status: {$webhook->http_status}\n";
            echo "Created: {$webhook->created_at}\n";
            echo str_repeat("-", 80) . "\n";
        }
    } else {
        echo "No webhooks found!\n";
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
