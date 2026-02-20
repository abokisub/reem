<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING PALMPAY WEBHOOK DETAILS ===\n\n";

$webhook = DB::table('palmpay_webhooks')
    ->orderBy('created_at', 'desc')
    ->first();

if ($webhook) {
    echo "Latest Webhook:\n";
    echo "ID: {$webhook->id}\n";
    echo "Event Type: {$webhook->event_type}\n";
    echo "Order No: {$webhook->order_no}\n";
    echo "Order Amount: {$webhook->order_amount}\n";
    echo "Account Reference: {$webhook->account_reference}\n";
    echo "Palmpay Reference: {$webhook->palmpay_reference}\n";
    echo "Verified: " . ($webhook->verified ? 'Yes' : 'No') . "\n";
    echo "Processed: " . ($webhook->processed ? 'Yes' : 'No') . "\n";
    echo "Created: {$webhook->created_at}\n\n";
    
    echo "Payload:\n";
    $payload = json_decode($webhook->payload, true);
    print_r($payload);
} else {
    echo "No webhooks found\n";
}
