<?php
/**
 * Reprocess Failed Webhook
 * Run: php reprocess_webhook.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\PalmPay\WebhookHandler;

echo "=== REPROCESSING FAILED WEBHOOK ===\n\n";

// Get the last failed webhook
$webhook = DB::table('palmpay_webhooks')
    ->where('verified', 0)
    ->orderBy('id', 'desc')
    ->first();

if (!$webhook) {
    echo "No failed webhooks found.\n";
    exit(0);
}

echo "Found webhook ID: {$webhook->id}\n";
echo "Created at: {$webhook->created_at}\n";
echo "Verified: " . ($webhook->verified ? 'Yes' : 'No') . "\n";
echo "Processed: " . ($webhook->processed ? 'Yes' : 'No') . "\n\n";

// Decode payload
$payload = json_decode($webhook->payload, true);

echo "Payload details:\n";
echo "  Account: {$payload['virtualAccountNo']}\n";
echo "  Amount: ₦" . ($payload['orderAmount'] / 100) . "\n";
echo "  Reference: {$payload['orderNo']}\n";
echo "  Payer: {$payload['payerAccountName']}\n\n";

// Reprocess with new signature verification
echo "Reprocessing with updated signature verification...\n";

$handler = new WebhookHandler();
$result = $handler->handle($payload, $payload['sign']);

echo "\nResult:\n";
echo "  Success: " . ($result['success'] ? 'YES ✓' : 'NO ✗') . "\n";
echo "  Message: {$result['message']}\n";

if (isset($result['transaction_id'])) {
    echo "  Transaction ID: {$result['transaction_id']}\n";
    
    // Check transaction
    $transaction = DB::table('transactions')
        ->where('transaction_id', $result['transaction_id'])
        ->first();
    
    if ($transaction) {
        echo "\nTransaction created successfully:\n";
        echo "  ID: {$transaction->transaction_id}\n";
        echo "  Amount: ₦{$transaction->amount}\n";
        echo "  Status: {$transaction->status}\n";
        echo "  Type: {$transaction->type}\n";
    }
}

echo "\n=== DONE ===\n";
