<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== Testing Credentials API Response ===\n\n";

// Get Kobopoint user
$user = User::where('email', 'kobopointng@gmail.com')->first();

if (!$user) {
    echo "User not found!\n";
    exit;
}

$company = $user->company;

if (!$company) {
    echo "Company not found!\n";
    exit;
}

echo "Company ID: {$company->id}\n";
echo "Company Name: {$company->name}\n\n";

// Test decryption
echo "Testing Webhook Secret Decryption:\n";

// Test using raw DB + decrypt (like the API does now)
$encryptedWebhookSecret = DB::table('companies')
    ->where('id', $company->id)
    ->value('webhook_secret');

$encryptedTestWebhookSecret = DB::table('companies')
    ->where('id', $company->id)
    ->value('test_webhook_secret');

try {
    $webhookSecret = decrypt($encryptedWebhookSecret);
    
    // Check if it's serialized (Laravel's encrypted cast serializes values)
    if (is_string($webhookSecret) && (strpos($webhookSecret, 's:') === 0 || strpos($webhookSecret, 'a:') === 0)) {
        $webhookSecret = unserialize($webhookSecret);
        echo "✓ webhook_secret (decrypted + unserialized): " . $webhookSecret . "\n";
    } else {
        echo "✓ webhook_secret (decrypted): " . $webhookSecret . "\n";
    }
} catch (\Exception $e) {
    echo "✗ webhook_secret ERROR: " . $e->getMessage() . "\n";
    $webhookSecret = null;
}

try {
    $testWebhookSecret = decrypt($encryptedTestWebhookSecret);
    
    // Check if it's serialized (Laravel's encrypted cast serializes values)
    if (is_string($testWebhookSecret) && (strpos($testWebhookSecret, 's:') === 0 || strpos($testWebhookSecret, 'a:') === 0)) {
        $testWebhookSecret = unserialize($testWebhookSecret);
        echo "✓ test_webhook_secret (decrypted + unserialized): " . $testWebhookSecret . "\n";
    } else {
        echo "✓ test_webhook_secret (decrypted): " . $testWebhookSecret . "\n";
    }
} catch (\Exception $e) {
    echo "✗ test_webhook_secret ERROR: " . $e->getMessage() . "\n";
    $testWebhookSecret = null;
}

echo "\n";

// Simulate the API response
echo "Simulating API Response:\n";
$response = [
    'business_id' => $company->business_id,
    'api_key' => $company->api_public_key,
    'secret_key' => $company->api_secret_key,
    'webhook_url' => $company->webhook_url,
    'webhook_secret' => $webhookSecret ?? null,
    'test_webhook_url' => $company->test_webhook_url,
    'test_webhook_secret' => $testWebhookSecret ?? null,
];

echo json_encode($response, JSON_PRETTY_PRINT) . "\n";

echo "\n=== End Test ===\n";
