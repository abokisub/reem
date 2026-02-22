<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Checking Webhook DLQ Issues ===\n\n";

// Get recent failed webhooks for company 4
$failedWebhooks = DB::table('company_webhook_logs')
    ->where('company_id', 4)
    ->where('status', 'failed')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "Recent Failed Webhooks for Kobopoint (Company ID: 4):\n\n";

foreach ($failedWebhooks as $webhook) {
    echo "Webhook Log ID: {$webhook->id}\n";
    echo "URL: {$webhook->url}\n";
    echo "Status: {$webhook->status}\n";
    echo "Attempts: {$webhook->attempts}\n";
    echo "Response Code: " . ($webhook->response_code ?? 'NULL') . "\n";
    echo "Error Message: " . ($webhook->error_message ?? 'NULL') . "\n";
    echo "Created: {$webhook->created_at}\n";
    echo "Last Attempt: " . ($webhook->last_attempt_at ?? 'NULL') . "\n";
    echo "\n" . str_repeat('-', 80) . "\n\n";
}

// Check company webhook configuration
$company = DB::table('companies')->where('id', 4)->first();

echo "Kobopoint Webhook Configuration:\n";
echo "- webhook_url: " . ($company->webhook_url ?? 'NULL') . "\n";
echo "- webhook_enabled: " . ($company->webhook_enabled ? 'YES' : 'NO') . "\n";
echo "- test_webhook_url: " . ($company->test_webhook_url ?? 'NULL') . "\n";

echo "\n=== End Check ===\n";
