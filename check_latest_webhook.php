<?php
/**
 * Check the latest webhook attempt to Kobopoint
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WebhookEvent;
use App\Models\Company;

echo "=== CHECKING LATEST KOBOPOINT WEBHOOK ===\n\n";

// Get Kobopoint company
$kobopoint = Company::where('name', 'LIKE', '%kobo%')->first();

if (!$kobopoint) {
    echo "❌ Kobopoint not found\n";
    exit(1);
}

echo "Company: {$kobopoint->name} (ID: {$kobopoint->id})\n";
echo "Webhook URL: {$kobopoint->webhook_url}\n";
echo "api_secret_key: " . substr($kobopoint->api_secret_key, 0, 20) . "...\n\n";

// Get latest webhook events for Kobopoint
$webhooks = WebhookEvent::where('company_id', $kobopoint->id)
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get();

if ($webhooks->isEmpty()) {
    echo "❌ No webhook events found\n";
    exit(1);
}

foreach ($webhooks as $webhook) {
    echo str_repeat("=", 80) . "\n";
    echo "Event ID: {$webhook->event_id}\n";
    echo "Status: {$webhook->status}\n";
    echo "Attempt Count: {$webhook->attempt_count}\n";
    echo "HTTP Status: " . ($webhook->http_status ?? 'N/A') . "\n";
    echo "Created: {$webhook->created_at}\n";
    echo "Last Attempt: " . ($webhook->last_attempt_at ?? 'N/A') . "\n\n";
    
    echo "Response from Kobopoint:\n";
    echo $webhook->response_body ?? 'No response';
    echo "\n\n";
    
    // Show what signature we sent
    $jsonPayload = json_encode($webhook->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $signature = hash_hmac('sha256', $jsonPayload, $kobopoint->api_secret_key);
    
    echo "Signature we sent: " . substr($signature, 0, 32) . "...\n";
    echo "Payload preview: " . substr($jsonPayload, 0, 200) . "...\n\n";
}

echo str_repeat("=", 80) . "\n";
echo "\nDone!\n";
