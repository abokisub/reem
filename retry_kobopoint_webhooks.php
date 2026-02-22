<?php

/**
 * Retry Failed Kobopoint Webhooks
 * 
 * This script retries all failed webhooks for Kobopoint (company_id = 4)
 * that are currently in the DLQ (Dead Letter Queue).
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WebhookEvent;
use App\Services\Webhook\OutgoingWebhookService;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "================================================================================\n";
echo "RETRYING FAILED KOBOPOINT WEBHOOKS\n";
echo "================================================================================\n";
echo "\n";

// Find all failed webhooks for Kobopoint
$failedWebhooks = WebhookEvent::where('company_id', 4)
    ->where('direction', 'outgoing')
    ->where('status', 'failed')
    ->orderBy('created_at', 'asc')
    ->get();

echo "Found " . $failedWebhooks->count() . " failed webhooks for Kobopoint\n";
echo "\n";

if ($failedWebhooks->isEmpty()) {
    echo "✅ No failed webhooks to retry!\n";
    echo "\n";
    exit(0);
}

// Display webhook details
echo "Webhook Details:\n";
echo "----------------\n";
foreach ($failedWebhooks as $webhook) {
    echo sprintf(
        "ID: %d | Event: %s | Attempts: %d | HTTP: %s | Created: %s\n",
        $webhook->id,
        $webhook->event_type,
        $webhook->attempt_count,
        $webhook->http_status ?? 'N/A',
        $webhook->created_at->format('Y-m-d H:i:s')
    );
}
echo "\n";

// Ask for confirmation
echo "Do you want to retry these webhooks? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'yes') {
    echo "\n❌ Retry cancelled.\n\n";
    exit(0);
}

echo "\n";
echo "Retrying webhooks...\n";
echo "--------------------\n";

$outgoingService = app(OutgoingWebhookService::class);

$results = [
    'total' => $failedWebhooks->count(),
    'succeeded' => 0,
    'failed' => 0,
];

foreach ($failedWebhooks as $webhook) {
    echo sprintf("Retrying webhook ID %d (Event: %s)... ", $webhook->id, $webhook->event_type);
    
    // Reset attempt count to allow retry
    $webhook->attempt_count = 0;
    $webhook->next_retry_at = now();
    $webhook->save();
    
    // Attempt delivery
    $success = $outgoingService->deliver($webhook);
    
    if ($success) {
        echo "✅ SUCCESS (HTTP " . $webhook->http_status . ")\n";
        $results['succeeded']++;
    } else {
        echo "❌ FAILED (HTTP " . ($webhook->http_status ?? 'N/A') . ")\n";
        $results['failed']++;
    }
    
    // Small delay between retries
    usleep(500000); // 0.5 seconds
}

echo "\n";
echo "================================================================================\n";
echo "RETRY RESULTS\n";
echo "================================================================================\n";
echo "\n";
echo "Total webhooks:     " . $results['total'] . "\n";
echo "✅ Succeeded:       " . $results['succeeded'] . "\n";
echo "❌ Failed:          " . $results['failed'] . "\n";
echo "\n";

if ($results['succeeded'] === $results['total']) {
    echo "🎉 All webhooks delivered successfully!\n";
} elseif ($results['succeeded'] > 0) {
    echo "⚠️  Some webhooks still failing. Check Kobopoint's logs.\n";
} else {
    echo "❌ All webhooks failed. Kobopoint's endpoint may still have issues.\n";
}

echo "\n";
echo "Next Steps:\n";
echo "-----------\n";
echo "1. Check webhook logs in admin panel\n";
echo "2. Verify Kobopoint sees the webhooks in their logs\n";
echo "3. Confirm customer balances are updating\n";
echo "\n";
