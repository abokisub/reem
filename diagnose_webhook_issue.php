<?php
/**
 * Diagnose Webhook Signature Issue
 * Run this on the live server to understand the exact problem
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;

echo "=== WEBHOOK SIGNATURE DIAGNOSTIC ===\n\n";

// Check Kobopoint
echo "1. KOBOPOINT WEBHOOK SECRET:\n";
echo str_repeat("-", 50) . "\n";

$kobopoint = Company::where('name', 'LIKE', '%kobopoint%')->first();
if ($kobopoint) {
    echo "Company ID: {$kobopoint->id}\n";
    echo "Company Name: {$kobopoint->name}\n";
    echo "Webhook URL: {$kobopoint->webhook_url}\n\n";
    
    $rawSecret = $kobopoint->getAttributes()['webhook_secret'];
    echo "Raw DB Value: " . substr($rawSecret, 0, 50) . "...\n";
    echo "Raw DB Length: " . strlen($rawSecret) . " bytes\n";
    echo "Is Serialized: " . (strpos($rawSecret, 's:') === 0 ? 'YES' : 'NO') . "\n\n";
    
    $decryptedSecret = $kobopoint->webhook_secret;
    echo "After Decryption: " . substr($decryptedSecret, 0, 50) . "...\n";
    echo "After Decryption Length: " . strlen($decryptedSecret) . " bytes\n";
    echo "Is Still Serialized: " . (is_string($decryptedSecret) && strpos($decryptedSecret, 's:') === 0 ? 'YES' : 'NO') . "\n\n";
    
    // Try to unserialize if needed
    if (is_string($decryptedSecret) && (strpos($decryptedSecret, 's:') === 0 || strpos($decryptedSecret, 'a:') === 0)) {
        $unserializedSecret = @unserialize($decryptedSecret);
        if ($unserializedSecret !== false) {
            echo "After Unserialize: " . substr($unserializedSecret, 0, 50) . "...\n";
            echo "After Unserialize Length: " . strlen($unserializedSecret) . " bytes\n\n";
        }
    }
    
    // Test signature generation
    echo "2. SIGNATURE GENERATION TEST:\n";
    echo str_repeat("-", 50) . "\n";
    
    $testPayload = [
        'event' => 'payment.received',
        'timestamp' => '2026-02-26T08:00:00Z',
        'data' => [
            'transaction_id' => 'TEST123',
            'amount' => 1000.00
        ]
    ];
    
    $jsonPayload = json_encode($testPayload);
    echo "Test Payload:\n{$jsonPayload}\n\n";
    
    // Method 1: Using raw decrypted secret
    $sig1 = hash_hmac('sha256', $jsonPayload, $decryptedSecret);
    echo "Signature (raw decrypted): {$sig1}\n\n";
    
    // Method 2: Using unserialized secret
    if (isset($unserializedSecret)) {
        $sig2 = hash_hmac('sha256', $jsonPayload, $unserializedSecret);
        echo "Signature (unserialized): {$sig2}\n\n";
    }
    
    // Method 3: What OutgoingWebhookService would generate
    $webhookSecret = $decryptedSecret;
    if (is_string($webhookSecret) && (strpos($webhookSecret, 's:') === 0 || strpos($webhookSecret, 'a:') === 0)) {
        $webhookSecret = unserialize($webhookSecret);
    }
    $sig3 = hash_hmac('sha256', $jsonPayload, $webhookSecret);
    echo "Signature (OutgoingWebhookService logic): {$sig3}\n\n";
    
} else {
    echo "Kobopoint company not found!\n\n";
}

// Check Amtpay for comparison
echo "\n3. AMTPAY WEBHOOK SECRET (for comparison):\n";
echo str_repeat("-", 50) . "\n";

$amtpay = Company::find(10);
if ($amtpay) {
    echo "Company ID: {$amtpay->id}\n";
    echo "Company Name: {$amtpay->name}\n\n";
    
    $rawSecret = $amtpay->getAttributes()['webhook_secret'];
    echo "Raw DB Value: " . substr($rawSecret, 0, 50) . "...\n";
    echo "Is Serialized: " . (strpos($rawSecret, 's:') === 0 ? 'YES' : 'NO') . "\n\n";
    
    $decryptedSecret = $amtpay->webhook_secret;
    echo "After Decryption: " . substr($decryptedSecret, 0, 50) . "...\n";
    echo "Is Still Serialized: " . (is_string($decryptedSecret) && strpos($decryptedSecret, 's:') === 0 ? 'YES' : 'NO') . "\n\n";
}

echo "\n4. RECOMMENDATION:\n";
echo str_repeat("-", 50) . "\n";
echo "Based on the diagnostic above:\n";
echo "- If webhook_secret is serialized in DB, the fix in commit b769562 should handle it\n";
echo "- If signatures don't match, there's a mismatch in how we generate vs how they verify\n";
echo "- Check if Kobopoint is using the correct secret from their dashboard\n";
echo "- Verify they're using raw request body (not parsed JSON)\n";
echo "- Confirm they're using HMAC SHA256\n";
