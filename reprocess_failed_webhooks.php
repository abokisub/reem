<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\PalmPay\WebhookHandler;

echo "=== Reprocessing Failed VA Credit Webhooks ===\n\n";

// Get all failed webhooks with the model error from today
$failed = DB::table('gateway_webhook_logs')
    ->where('status', 'failed')
    ->where('event_type', 'VIRTUAL_ACCOUNT_CASH_IN')
    ->whereDate('created_at', today())
    ->get();

echo "Found {$failed->count()} failed webhooks to reprocess\n\n";

if ($failed->isEmpty()) {
    echo "Nothing to reprocess.\n";
    exit(0);
}

$handler = app(WebhookHandler::class);
$success = 0;
$fail = 0;

foreach ($failed as $log) {
    $payload = json_decode($log->payload, true);
    if (!$payload) {
        echo "❌ ID {$log->id}: Invalid payload\n";
        $fail++;
        continue;
    }

    echo "Processing webhook ID {$log->id} | OrderNo: " . ($payload['orderNo'] ?? 'N/A') . " | Amount: " . ($payload['orderAmount'] ?? 0) . "\n";

    try {
        // Reset status to pending so handler will process it
        DB::table('gateway_webhook_logs')->where('id', $log->id)->update([
            'status' => 'pending',
            'error_message' => null,
            'retry_count' => 0,
        ]);

        $result = $handler->handle($payload);

        if ($result['success'] ?? false) {
            echo "  ✅ Success\n";
            $success++;
        } else {
            echo "  ❌ Failed: " . ($result['message'] ?? 'unknown') . "\n";
            $fail++;
        }
    } catch (\Exception $e) {
        echo "  ❌ Exception: " . $e->getMessage() . "\n";
        $fail++;
    }
}

echo "\n=== Done: {$success} succeeded, {$fail} failed ===\n";
