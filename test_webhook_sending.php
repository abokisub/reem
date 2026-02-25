<?php

/**
 * Test Webhook Sending to Amtpay
 * Run this on PointWave LIVE server to simulate webhook sending
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;

echo "=== Testing Webhook Signature Generation for Amtpay ===\n\n";

// Get Amtpay company
$company = Company::find(10);

if (!$company) {
    echo "ERROR: Amtpay (company_id: 10) not found!\n";
    exit(1);
}

echo "Company: {$company->name}\n";
echo "Webhook URL: {$company->webhook_url}\n\n";

// Test payload (similar to what we send)
$payload = [
    'event' => 'payment.success',
    'event_id' => 'test-' . uniqid(),
    'timestamp' => date('c'),
    'data' => [
        'transaction_id' => 'txn_test123',
        'amount' => '100.00',
        'fee' => '0.60',
        'net_amount' => '99.40',
        'reference' => 'REF_TEST123',
        'status' => 'success',
        'customer' => [
            'account_number' => '6604877046',
            'sender_name' => 'TEST SENDER',
            'sender_account' => '1234567890',
            'sender_bank' => 'TEST BANK'
        ],
        'narration' => 'Test transaction',
        'created_at' => date('c')
    ]
];

// Get webhook secret (this will auto-decrypt via model)
$webhookSecret = $company->webhook_secret;

echo "=== Webhook Secret Info ===\n";
echo "Secret (first 20 chars): " . substr($webhookSecret, 0, 20) . "...\n";
echo "Secret length: " . strlen($webhookSecret) . "\n";

// Check if it's serialized
if (is_string($webhookSecret) && (strpos($webhookSecret, 's:') === 0 || strpos($webhookSecret, 'a:') === 0)) {
    echo "⚠️  Secret is SERIALIZED format\n";
    echo "Unserializing...\n";
    $webhookSecret = unserialize($webhookSecret);
    echo "After unserialize (first 20 chars): " . substr($webhookSecret, 0, 20) . "...\n";
    echo "After unserialize length: " . strlen($webhookSecret) . "\n";
} else {
    echo "✅ Secret is plain text\n";
}

echo "\n=== Signature Calculation ===\n";

// Convert payload to JSON (exactly as OutgoingWebhookService does)
$jsonPayload = json_encode($payload);
echo "Payload length: " . strlen($jsonPayload) . "\n";
echo "Payload (first 100 chars): " . substr($jsonPayload, 0, 100) . "...\n\n";

// Calculate signature
$signature = hash_hmac('sha256', $jsonPayload, $webhookSecret);

echo "Generated signature: {$signature}\n\n";

echo "=== What Amtpay Should Do ===\n";
echo "1. Receive webhook with header: X-PointWave-Signature: {$signature}\n";
echo "2. Get raw body: \$payload = \$request->getContent();\n";
echo "3. Calculate: hash_hmac('sha256', \$payload, '{$webhookSecret}');\n";
echo "4. Compare with received signature\n\n";

echo "=== Test with Amtpay's Secret ===\n";
$amtpaySecret = 'whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24';
$amtpaySignature = hash_hmac('sha256', $jsonPayload, $amtpaySecret);
echo "If Amtpay uses their secret: {$amtpaySignature}\n\n";

if ($signature === $amtpaySignature) {
    echo "✅ MATCH! Secrets are the same.\n";
} else {
    echo "❌ MISMATCH! Secrets are different.\n";
    echo "\nPointWave is using: " . substr($webhookSecret, 0, 30) . "...\n";
    echo "Amtpay is using: " . substr($amtpaySecret, 0, 30) . "...\n";
}
