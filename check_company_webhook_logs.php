<?php

/**
 * Check Company Webhook Logs for Kobopoint
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CompanyWebhookLog;

echo "\n";
echo "================================================================================\n";
echo "KOBOPOINT WEBHOOK LOGS (CompanyWebhookLog table)\n";
echo "================================================================================\n";
echo "\n";

// Get all webhook logs for Kobopoint (company_id = 4)
$webhooks = CompanyWebhookLog::where('company_id', 4)
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

if ($webhooks->isEmpty()) {
    echo "No webhook logs found for Kobopoint in company_webhook_logs table.\n";
    echo "\n";
    exit(0);
}

echo "Total webhook logs found: " . $webhooks->count() . "\n";
echo "\n";

// Group by status
$byStatus = $webhooks->groupBy('status');

echo "Summary by Status:\n";
echo "------------------\n";
foreach ($byStatus as $status => $group) {
    $icon = match($status) {
        'success', 'delivered' => '✅',
        'failed' => '❌',
        'pending' => '⏳',
        default => '❓'
    };
    echo sprintf("%s %s: %d\n", $icon, ucfirst($status ?? 'unknown'), $group->count());
}
echo "\n";

// Show detailed list
echo "Detailed Webhook List (most recent first):\n";
echo "-------------------------------------------\n";
printf("%-5s %-20s %-12s %-8s %-5s %-20s\n", 
    "ID", "Event Type", "Status", "HTTP", "Tries", "Created At");
echo str_repeat("-", 80) . "\n";

foreach ($webhooks as $webhook) {
    $icon = match($webhook->status) {
        'success', 'delivered' => '✅',
        'failed' => '❌',
        'pending' => '⏳',
        default => '❓'
    };
    
    printf("%s %-3d %-20s %-12s %-8s %-5d %-20s\n",
        $icon,
        $webhook->id,
        substr($webhook->event_type ?? 'unknown', 0, 20),
        $webhook->status ?? 'unknown',
        $webhook->http_status ?? 'N/A',
        $webhook->attempt_count ?? 0,
        $webhook->created_at->format('Y-m-d H:i:s')
    );
}

echo "\n";

// Show failed webhooks details if any
$failed = $webhooks->where('status', 'failed');
if ($failed->isNotEmpty()) {
    echo "Failed Webhooks Details:\n";
    echo "------------------------\n";
    foreach ($failed as $webhook) {
        echo sprintf("\nWebhook ID: %d\n", $webhook->id);
        echo sprintf("Event Type: %s\n", $webhook->event_type ?? 'unknown');
        echo sprintf("HTTP Status: %s\n", $webhook->http_status ?? 'N/A');
        echo sprintf("Attempts: %d\n", $webhook->attempt_count ?? 0);
        echo sprintf("Last Attempt: %s\n", $webhook->last_attempt_at ? $webhook->last_attempt_at->format('Y-m-d H:i:s') : 'Never');
        echo sprintf("Next Retry: %s\n", $webhook->next_retry_at ? $webhook->next_retry_at->format('Y-m-d H:i:s') : 'N/A');
        echo sprintf("Response: %s\n", substr($webhook->response_body ?? 'N/A', 0, 100));
        echo "\n";
    }
}

// Show successful webhooks
$successful = $webhooks->whereIn('status', ['success', 'delivered']);
if ($successful->isNotEmpty()) {
    echo "✅ SUCCESS: {$successful->count()} webhooks delivered successfully!\n";
    echo "\n";
    echo "Recent successful deliveries:\n";
    foreach ($successful->take(5) as $webhook) {
        echo sprintf("  - %s (HTTP %d) at %s\n",
            $webhook->event_type ?? 'unknown',
            $webhook->http_status ?? 0,
            $webhook->last_attempt_at ? $webhook->last_attempt_at->format('Y-m-d H:i:s') : $webhook->created_at->format('Y-m-d H:i:s')
        );
    }
    echo "\n";
}

echo "================================================================================\n";
echo "\n";
