<?php
/**
 * Check webhook logs from CompanyWebhookLog table
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CompanyWebhookLog;
use App\Models\Company;

echo "=== CHECKING KOBOPOINT WEBHOOK LOGS ===\n\n";

// Get Kobopoint company
$kobopoint = Company::where('name', 'LIKE', '%kobo%')->first();

if (!$kobopoint) {
    echo "❌ Kobopoint not found\n";
    exit(1);
}

echo "Company: {$kobopoint->name} (ID: {$kobopoint->id})\n";
echo "Webhook URL: {$kobopoint->webhook_url}\n";
echo "api_secret_key: " . substr($kobopoint->api_secret_key, 0, 20) . "...\n\n";

// Get latest webhook logs for Kobopoint
$webhooks = CompanyWebhookLog::where('company_id', $kobopoint->id)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($webhooks->isEmpty()) {
    echo "❌ No webhook logs found\n";
    exit(1);
}

echo "Found {$webhooks->count()} recent webhook(s):\n\n";

foreach ($webhooks as $webhook) {
    echo str_repeat("=", 80) . "\n";
    echo "ID: {$webhook->id}\n";
    echo "Event Type: {$webhook->event_type}\n";
    echo "Status: {$webhook->status}\n";
    echo "Attempt Number: {$webhook->attempt_number}\n";
    echo "HTTP Status: " . ($webhook->http_status ?? 'N/A') . "\n";
    echo "Created: {$webhook->created_at}\n";
    echo "Last Attempt: " . ($webhook->last_attempt_at ?? 'N/A') . "\n";
    echo "Next Retry: " . ($webhook->next_retry_at ?? 'N/A') . "\n\n";
    
    echo "Response from Kobopoint:\n";
    echo $webhook->response_body ?? 'No response';
    echo "\n\n";
    
    if ($webhook->error_message) {
        echo "Error Message: {$webhook->error_message}\n\n";
    }
    
    // Show what signature was sent
    if ($webhook->payload) {
        $payloadJson = json_encode($webhook->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        // Check which secret was used
        $company = $webhook->company;
        $secret = $webhook->is_test ? $company->test_webhook_secret : $company->webhook_secret;
        
        if ($secret) {
            $signature = hash_hmac('sha256', $payloadJson, $secret);
            echo "Signature sent (using webhook_secret): " . substr($signature, 0, 32) . "...\n";
        }
        
        // Show what it SHOULD be with api_secret_key
        $correctSignature = hash_hmac('sha256', $payloadJson, $kobopoint->api_secret_key);
        echo "Correct signature (using api_secret_key): " . substr($correctSignature, 0, 32) . "...\n\n";
    }
}

echo str_repeat("=", 80) . "\n";
echo "\nDone!\n";
