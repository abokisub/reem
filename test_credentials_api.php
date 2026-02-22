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
try {
    $webhookSecret = $company->webhook_secret;
    echo "✓ webhook_secret: " . ($webhookSecret ?: 'EMPTY/NULL') . "\n";
} catch (\Exception $e) {
    echo "✗ webhook_secret ERROR: " . $e->getMessage() . "\n";
}

try {
    $testWebhookSecret = $company->test_webhook_secret;
    echo "✓ test_webhook_secret: " . ($testWebhookSecret ?: 'EMPTY/NULL') . "\n";
} catch (\Exception $e) {
    echo "✗ test_webhook_secret ERROR: " . $e->getMessage() . "\n";
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
