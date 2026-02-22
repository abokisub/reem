<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Webhook Secrets Diagnostic ===\n\n";

// Get Kobopoint company raw data
$company = DB::table('companies')->where('id', 4)->first();

if (!$company) {
    echo "Company not found!\n";
    exit;
}

echo "Company ID: {$company->id}\n";
echo "Company Name: {$company->name}\n\n";

echo "RAW DATABASE VALUES:\n";
echo "-------------------\n";

// Show raw encrypted values
echo "webhook_secret (raw encrypted):\n";
echo substr($company->webhook_secret, 0, 100) . "...\n";
echo "Length: " . strlen($company->webhook_secret) . " bytes\n\n";

echo "test_webhook_secret (raw encrypted):\n";
echo substr($company->test_webhook_secret, 0, 100) . "...\n";
echo "Length: " . strlen($company->test_webhook_secret) . " bytes\n\n";

// Check if it looks like serialized PHP
if (strpos($company->webhook_secret, 's:') === 0) {
    echo "⚠️  WARNING: webhook_secret appears to be PHP serialized format!\n";
    echo "   This means it's NOT properly encrypted.\n\n";
}

if (strpos($company->test_webhook_secret, 's:') === 0) {
    echo "⚠️  WARNING: test_webhook_secret appears to be PHP serialized format!\n";
    echo "   This means it's NOT properly encrypted.\n\n";
}

// Try to decrypt
echo "DECRYPTION TEST:\n";
echo "----------------\n";

try {
    $decrypted = decrypt($company->webhook_secret);
    echo "✓ webhook_secret decrypted successfully\n";
    echo "  Value: $decrypted\n";
    echo "  Type: " . gettype($decrypted) . "\n";
    
    // Check if decrypted value is still serialized
    if (is_string($decrypted) && strpos($decrypted, 's:') === 0) {
        echo "  ⚠️  PROBLEM: Decrypted value is STILL serialized!\n";
        echo "  This means the value was double-encrypted or improperly stored.\n";
    }
} catch (\Exception $e) {
    echo "✗ webhook_secret decryption FAILED\n";
    echo "  Error: " . $e->getMessage() . "\n";
}

echo "\n";

try {
    $decrypted = decrypt($company->test_webhook_secret);
    echo "✓ test_webhook_secret decrypted successfully\n";
    echo "  Value: $decrypted\n";
    echo "  Type: " . gettype($decrypted) . "\n";
    
    if (is_string($decrypted) && strpos($decrypted, 's:') === 0) {
        echo "  ⚠️  PROBLEM: Decrypted value is STILL serialized!\n";
        echo "  This means the value was double-encrypted or improperly stored.\n";
    }
} catch (\Exception $e) {
    echo "✗ test_webhook_secret decryption FAILED\n";
    echo "  Error: " . $e->getMessage() . "\n";
}

echo "\n=== End Diagnostic ===\n";
