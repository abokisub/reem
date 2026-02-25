<?php

/**
 * Test Actual Webhook Sending to Amtpay
 * Run this on PointWave LIVE server to test real webhook delivery
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use Illuminate\Support\Facades\Http;

echo "=== Testing Live Webhook to Amtpay ===\n\n";

// Get Amtpay company
$company = Company::find(10);

if (!$company) {
    echo "❌ ERROR: Amtpay (company_id: 10) not found!\n";
    exit(1);
}

echo "Company: {$company->name}\n";
echo "Webhook URL: {$company->webhook_url}\n\n";

// Test payload (similar to real transaction)
$payload = [
    'event' => 'payment.success',
    'event_id' => 'test-' . uniqid(),
    'timestamp' => date('c'),
    'data' => [
        'transaction_id' => 'TEST_' . strtoupper(uniqid()),
        'reference' => 'REF_TEST_' . time(),
        'session_id' => 'session_test_' . uniqid(),
        'type' => 'va_deposit',
        'amount' => 100.00,
        'fee' => 0.60,
        'net_amount' => 99.40,
        'currency' => 'NGN',
        'status' => 'success',
        'settlement_status' => 'pending',
        'customer' => [
            'name' => 'TEST CUSTOMER',
            'account_number' => '1234567890',
            'bank_name' => 'TEST BANK',
            'email' => null,
        ],
        'virtual_account' => [
            'account_number' => '6604877046',
            'account_name' => 'AMTPAY TEST',
        ],
        'created_at' => date('c'),
        'updated_at' => date('c'),
    ]
];

// Get webhook secret (auto-decrypted by model)
$webhookSecret = $company->webhook_secret;

echo "=== Step 1: Check Webhook Secret ===\n";
echo "Secret type: " . gettype($webhookSecret) . "\n";
echo "Secret length: " . strlen($webhookSecret) . "\n";
echo "Secret (first 30 chars): " . substr($webhookSecret, 0, 30) . "...\n";

// Apply the fix: Ensure webhook secret is plain string (not serialized)
if (is_string($webhookSecret) && (strpos($webhookSecret, 's:') === 0 || strpos($webhookSecret, 'a:') === 0)) {
    echo "⚠️  Secret is SERIALIZED - unserializing...\n";
    $webhookSecret = unserialize($webhookSecret);
    echo "After unserialize length: " . strlen($webhookSecret) . "\n";
    echo "After unserialize (first 30 chars): " . substr($webhookSecret, 0, 30) . "...\n";
} else {
    echo "✅ Secret is already plain text\n";
}

echo "\n=== Step 2: Generate Signature ===\n";

// Convert payload to JSON (exactly as OutgoingWebhookService does)
$jsonPayload = json_encode($payload);
echo "Payload JSON length: " . strlen($jsonPayload) . " bytes\n";

// Calculate HMAC-SHA256 signature
$signature = hash_hmac('sha256', $jsonPayload, $webhookSecret);
echo "Generated signature: {$signature}\n";

echo "\n=== Step 3: Send Webhook to Amtpay ===\n";

try {
    $eventId = 'test-' . uniqid();
    $timestamp = time();
    
    echo "Sending POST to: {$company->webhook_url}\n";
    echo "Headers:\n";
    echo "  Content-Type: application/json\n";
    echo "  X-PointWave-Signature: {$signature}\n";
    echo "  X-PointWave-Event-ID: {$eventId}\n";
    echo "  X-PointWave-Event-Type: payment.success\n";
    echo "  X-PointWave-Timestamp: {$timestamp}\n\n";
    
    // Send HTTP POST request
    $response = Http::timeout(30)
        ->withHeaders([
            'Content-Type' => 'application/json',
            'X-PointWave-Signature' => $signature,
            'X-PointWave-Event-ID' => $eventId,
            'X-PointWave-Event-Type' => 'payment.success',
            'X-PointWave-Timestamp' => $timestamp,
        ])
        ->post($company->webhook_url, $payload);
    
    $statusCode = $response->status();
    $responseBody = $response->body();
    
    echo "=== Response ===\n";
    echo "Status Code: {$statusCode}\n";
    echo "Response Body:\n";
    echo $responseBody . "\n\n";
    
    if ($statusCode >= 200 && $statusCode < 300) {
        echo "✅ SUCCESS! Webhook delivered successfully\n";
        echo "Amtpay accepted the webhook with valid signature\n";
    } else {
        echo "❌ FAILED! Status code: {$statusCode}\n";
        
        // Check if it's a signature error
        if (stripos($responseBody, 'signature') !== false || stripos($responseBody, 'invalid') !== false) {
            echo "\n⚠️  This looks like a signature validation error\n";
            echo "Possible causes:\n";
            echo "1. PHP-FPM hasn't restarted yet (old code still in memory)\n";
            echo "2. Amtpay is using a different webhook secret\n";
            echo "3. Amtpay's signature validation logic has an issue\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
}

echo "\n=== What Amtpay Should See ===\n";
echo "When Amtpay receives this webhook, they should:\n";
echo "1. Get signature from header: X-PointWave-Signature\n";
echo "2. Get raw body: \$payload = \$request->getContent();\n";
echo "3. Calculate: hash_hmac('sha256', \$payload, 'their_secret');\n";
echo "4. Compare calculated signature with received signature\n\n";

echo "Expected signature: {$signature}\n";
echo "Webhook secret used: " . substr($webhookSecret, 0, 40) . "...\n\n";

echo "=== Verification ===\n";
echo "If Amtpay's secret is: whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24\n";
$testSignature = hash_hmac('sha256', $jsonPayload, 'whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24');
echo "They should calculate: {$testSignature}\n\n";

if ($signature === $testSignature) {
    echo "✅ MATCH! Signatures are identical\n";
    echo "The webhook secret in database matches Amtpay's secret\n";
} else {
    echo "❌ MISMATCH! Signatures are different\n";
    echo "PointWave calculated: {$signature}\n";
    echo "Amtpay would calculate: {$testSignature}\n";
    echo "\nThis means the webhook secrets don't match!\n";
}

echo "\n=== Next Steps ===\n";
if ($statusCode >= 200 && $statusCode < 300) {
    echo "✅ Everything is working! Amtpay can now:\n";
    echo "1. Remove the signature bypass code\n";
    echo "2. Enable full signature verification\n";
    echo "3. Test with a real transaction\n";
} else {
    echo "⚠️  Webhook delivery failed. Check:\n";
    echo "1. Is PHP-FPM restarted? (code might be cached)\n";
    echo "2. Is Amtpay's webhook endpoint working?\n";
    echo "3. Does Amtpay have the correct webhook secret?\n";
    echo "4. Check Amtpay's logs for the exact error\n";
}

echo "\n=== Done ===\n";
