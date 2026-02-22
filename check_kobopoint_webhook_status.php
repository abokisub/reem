<?php

/**
 * Check Kobopoint Webhook Status
 * 
 * Shows all webhooks for Kobopoint with their current status
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WebhookEvent;

echo "\n";
echo "================================================================================\n";
echo "KOBOPOINT WEBHOOK STATUS\n";
echo "================================================================================\n";
echo "\n";

// Get all webhooks for Kobopoint (company_id = 4)
$webhooks = WebhookEvent::where('company_id', 4)
    ->where('direction', 'outgoing')
    ->orderBy('created_at', 'desc')
    ->limit(20)
    ->get();

if ($webhooks->isEmpty()) {
    echo "No webhooks found for Kobopoint.\n";
    echo "\n";
    exit(0);
}

echo "Total webhooks found: " . $webhooks->count() . "\n";
echo "\n";

// Group by status
$byStatus = $webhooks->groupBy('status');

echo "Summary by Status:\n";
echo "------------------\n";
foreach ($byStatus as $status => $group) {
    $icon = match($status) {
        'delivered' => '✅',
        'failed' => '❌',
        'pending' => '⏳',
        default => '❓'
    };
    echo sprintf("%s %s: %d\n", $icon, ucfirst($status), $group->count());
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
        'delivered' => '✅',
        'failed' => '❌',
        'pending' => '⏳',
        default => '❓'
    };
    
    printf("%s %-3d %-20s %-12s %-8s %-5d %-20s\n",
        $icon,
        $webhook->id,
        substr($webhook->event_type, 0, 20),
        $webhook->status,
        $webhook->http_status ?? 'N/A',
        $webhook->attempt_count,
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
        echo sprintf("Event Type: %s\n", $webhook->event_type);
        echo sprintf("HTTP Status: %s\n", $webhook->http_status ?? 'N/A');
        echo sprintf("Attempts: %d\n", $webhook->attempt_count);
        echo sprintf("Last Attempt: %s\n", $webhook->last_attempt_at ? $webhook->last_attempt_at->format('Y-m-d H:i:s') : 'Never');
        echo sprintf("Next Retry: %s\n", $webhook->next_retry_at ? $webhook->next_retry_at->format('Y-m-d H:i:s') : 'N/A');
        echo sprintf("Response: %s\n", substr($webhook->response_body ?? 'N/A', 0, 100));
        echo "\n";
    }
}

// Show delivered webhooks count
$delivered = $webhooks->where('status', 'delivered');
if ($delivered->isNotEmpty()) {
    echo "✅ SUCCESS: {$delivered->count()} webhooks delivered successfully!\n";
    echo "\n";
    echo "Recent successful deliveries:\n";
    foreach ($delivered->take(5) as $webhook) {
        echo sprintf("  - %s (HTTP %d) at %s\n",
            $webhook->event_type,
            $webhook->http_status,
            $webhook->last_attempt_at->format('Y-m-d H:i:s')
        );
    }
    echo "\n";
}

echo "================================================================================\n";
echo "\n";
