<?php

/**
 * Check Amtpay's Webhook Secret in Database
 * Run this on PointWave LIVE server
 */

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use Illuminate\Support\Facades\DB;

echo "=== Checking Amtpay Webhook Secret ===\n\n";

// Get Amtpay company
$company = Company::find(10);

if (!$company) {
    echo "❌ ERROR: Amtpay (company_id: 10) not found!\n";
    exit(1);
}

echo "Company ID: {$company->id}\n";
echo "Company Name: {$company->name}\n";
echo "Webhook URL: {$company->webhook_url}\n\n";

// Get raw encrypted value from database
$rawData = DB::table('companies')
    ->where('id', 10)
    ->select('webhook_secret')
    ->first();

echo "=== Raw Database Value ===\n";
if ($rawData && $rawData->webhook_secret) {
    echo "Raw value (first 100 chars): " . substr($rawData->webhook_secret, 0, 100) . "...\n";
    echo "Raw value length: " . strlen($rawData->webhook_secret) . "\n\n";
} else {
    echo "❌ No webhook secret in database!\n\n";
}

// Get decrypted value via model (Laravel auto-decrypts)
$webhookSecret = $company->webhook_secret;

echo "=== After Laravel Decryption ===\n";
echo "Type: " . gettype($webhookSecret) . "\n";
echo "Length: " . strlen($webhookSecret) . "\n";
echo "Value (first 50 chars): " . substr($webhookSecret, 0, 50) . "...\n";
echo "Value (last 20 chars): ..." . substr($webhookSecret, -20) . "\n\n";

// Check if it's serialized
$isSerialized = false;
if (is_string($webhookSecret) && (strpos($webhookSecret, 's:') === 0 || strpos($webhookSecret, 'a:') === 0)) {
    echo "⚠️  SECRET IS SERIALIZED!\n";
    echo "Format detected: PHP serialized string\n\n";
    $isSerialized = true;
    
    // Try to unserialize
    try {
        $unserialized = unserialize($webhookSecret);
        echo "=== After Unserialization ===\n";
        echo "Type: " . gettype($unserialized) . "\n";
        echo "Length: " . strlen($unserialized) . "\n";
        echo "Value (first 50 chars): " . substr($unserialized, 0, 50) . "...\n";
        echo "Value (last 20 chars): ..." . substr($unserialized, -20) . "\n\n";
        
        $webhookSecret = $unserialized;
    } catch (\Exception $e) {
        echo "❌ Failed to unserialize: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "✅ SECRET IS PLAIN TEXT (not serialized)\n\n";
}

// Compare with expected Amtpay secret
$expectedSecret = 'whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24';

echo "=== Comparison with Amtpay's Secret ===\n";
echo "Expected: {$expectedSecret}\n";
echo "Actual:   {$webhookSecret}\n\n";

if ($webhookSecret === $expectedSecret) {
    echo "✅ MATCH! Secrets are identical\n";
} else {
    echo "❌ MISMATCH! Secrets are different\n";
    
    // Show character-by-character comparison for first 50 chars
    echo "\nCharacter comparison (first 50 chars):\n";
    for ($i = 0; $i < min(50, strlen($expectedSecret), strlen($webhookSecret)); $i++) {
        $e = $expectedSecret[$i] ?? '';
        $a = $webhookSecret[$i] ?? '';
        if ($e !== $a) {
            echo "Position {$i}: Expected '{$e}' but got '{$a}'\n";
        }
    }
}

echo "\n=== Test Signature Generation ===\n";
$testPayload = '{"event":"payment.success","data":{"amount":100}}';
echo "Test payload: {$testPayload}\n\n";

$signature1 = hash_hmac('sha256', $testPayload, $webhookSecret);
$signature2 = hash_hmac('sha256', $testPayload, $expectedSecret);

echo "Signature with database secret: {$signature1}\n";
echo "Signature with expected secret: {$signature2}\n\n";

if ($signature1 === $signature2) {
    echo "✅ Signatures MATCH - secrets are functionally identical\n";
} else {
    echo "❌ Signatures DIFFER - secrets are NOT the same\n";
}

echo "\n=== Code Fix Status ===\n";
$serviceFile = __DIR__ . '/app/Services/Webhook/OutgoingWebhookService.php';
if (file_exists($serviceFile)) {
    $content = file_get_contents($serviceFile);
    if (strpos($content, 'unserialize($webhookSecret)') !== false) {
        echo "✅ OutgoingWebhookService has the unserialization fix\n";
    } else {
        echo "❌ OutgoingWebhookService DOES NOT have the fix\n";
    }
} else {
    echo "❌ OutgoingWebhookService file not found\n";
}

$controllerFile = __DIR__ . '/app/Http/Controllers/API/TransactionController.php';
if (file_exists($controllerFile)) {
    $content = file_get_contents($controllerFile);
    if (strpos($content, 'unserialize($webhookSecret)') !== false) {
        echo "✅ TransactionController has the unserialization fix\n";
    } else {
        echo "❌ TransactionController DOES NOT have the fix\n";
    }
} else {
    echo "❌ TransactionController file not found\n";
}

echo "\n=== Conclusion ===\n";
if ($isSerialized) {
    echo "⚠️  The webhook secret is stored in SERIALIZED format\n";
    echo "The fix in the code will unserialize it before use\n";
    echo "BUT: PHP-FPM needs to be restarted to load the new code!\n\n";
    echo "Action required:\n";
    echo "1. Restart PHP-FPM to clear OPcache\n";
    echo "2. Run test_amtpay_webhook_live.php to test actual delivery\n";
} else {
    echo "✅ The webhook secret is in plain text format\n";
    echo "No unserialization needed\n\n";
    if ($webhookSecret === $expectedSecret) {
        echo "✅ Secret matches Amtpay's expected value\n";
        echo "Webhooks should work correctly!\n";
    } else {
        echo "❌ Secret does NOT match Amtpay's expected value\n";
        echo "This will cause signature verification failures\n";
    }
}

echo "\n=== Done ===\n";
