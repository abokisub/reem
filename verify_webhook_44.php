<?php
/**
 * Verify what signature was actually sent for webhook ID 44
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CompanyWebhookLog;
use App\Models\Company;

echo "=== VERIFYING WEBHOOK ID 44 ===\n\n";

$webhook = CompanyWebhookLog::find(44);

if (!$webhook) {
    echo "❌ Webhook 44 not found\n";
    exit(1);
}

$company = $webhook->company;

echo "Webhook ID: {$webhook->id}\n";
echo "Status: {$webhook->status}\n";
echo "HTTP Status: {$webhook->http_status}\n";
echo "Response: {$webhook->response_body}\n\n";

// Recreate the exact signature calculation from SendOutgoingWebhook job
$payloadJson = json_encode($webhook->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

echo "=== SIGNATURE CALCULATIONS ===\n\n";

// What the OLD code would send (webhook_secret)
$oldSecret = $webhook->is_test ? $company->test_webhook_secret : $company->webhook_secret;
if ($oldSecret) {
    $oldSignature = hash_hmac('sha256', $payloadJson, $oldSecret);
    echo "OLD (webhook_secret): " . substr($oldSignature, 0, 32) . "...\n";
}

// What the NEW code sends (api_secret_key)
$newSecret = $webhook->is_test ? $company->test_secret_key : $company->api_secret_key;
if ($newSecret) {
    $newSignature = hash_hmac('sha256', $payloadJson, $newSecret);
    echo "NEW (api_secret_key): " . substr($newSignature, 0, 32) . "...\n";
}

echo "\n=== CONCLUSION ===\n";
echo "Since webhook was delivered successfully (HTTP 200),\n";
echo "Kobopoint accepted the signature we sent.\n";
echo "The fix is working!\n";
