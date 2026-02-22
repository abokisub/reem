<?php

/**
 * Retry Failed Company Webhooks for Kobopoint
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CompanyWebhookLog;
use App\Jobs\SendOutgoingWebhook;

echo "\n";
echo "================================================================================\n";
echo "RETRYING FAILED KOBOPOINT WEBHOOKS\n";
echo "================================================================================\n";
echo "\n";

// Find all failed webhooks for Kobopoint
$failedWebhooks = CompanyWebhookLog::where('company_id', 4)
    ->where('status', 'delivery_failed')
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
        "ID: %d | Event: %s | HTTP: %s | Created: %s\n",
        $webhook->id,
        $webhook->event_type,
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

$results = [
    'total' => $failedWebhooks->count(),
    'dispatched' => 0,
];

foreach ($failedWebhooks as $webhook) {
    echo sprintf("Retrying webhook ID %d (Event: %s)... ", $webhook->id, $webhook->event_type);
    
    try {
        // Reset status to pending
        $webhook->update([
            'status' => 'pending',
            'attempt_number' => 0,
            'next_retry_at' => now(),
        ]);
        
        // Dispatch the job to send the webhook
        SendOutgoingWebhook::dispatch($webhook);
        
        echo "✅ DISPATCHED\n";
        $results['dispatched']++;
        
        // Small delay between dispatches
        usleep(200000); // 0.2 seconds
        
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\n";
echo "================================================================================\n";
echo "RETRY RESULTS\n";
echo "================================================================================\n";
echo "\n";
echo "Total webhooks:     " . $results['total'] . "\n";
echo "✅ Dispatched:      " . $results['dispatched'] . "\n";
echo "\n";

if ($results['dispatched'] === $results['total']) {
    echo "🎉 All webhooks dispatched for retry!\n";
    echo "\n";
    echo "The webhooks are being processed in the background.\n";
    echo "Check the status in a few seconds with:\n";
    echo "  php check_company_webhook_logs.php\n";
} else {
    echo "⚠️  Some webhooks failed to dispatch.\n";
}

echo "\n";
echo "Next Steps:\n";
echo "-----------\n";
echo "1. Wait 10-30 seconds for jobs to process\n";
echo "2. Run: php check_company_webhook_logs.php\n";
echo "3. Verify webhooks show 'success' status\n";
echo "4. Ask Kobopoint to check their logs\n";
echo "5. Confirm customer balances are updating\n";
echo "\n";
