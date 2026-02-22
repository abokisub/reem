<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Fixing Double-Encrypted Webhook Secrets ===\n\n";

// Get Kobopoint company
$company = DB::table('companies')->where('id', 4)->first();

if (!$company) {
    echo "Company not found!\n";
    exit;
}

echo "Company ID: {$company->id}\n";
echo "Company Name: {$company->name}\n\n";

echo "ANALYZING webhook_secret:\n";
echo "-------------------------\n";

// Get raw value
$rawValue = $company->webhook_secret;
echo "Raw DB value (first 100 chars): " . substr($rawValue, 0, 100) . "...\n";
echo "Length: " . strlen($rawValue) . " bytes\n\n";

// Try to decrypt
try {
    $decrypted = decrypt($rawValue);
    echo "✓ First decryption successful\n";
    echo "  Type: " . gettype($decrypted) . "\n";
    echo "  Value: " . substr($decrypted, 0, 100) . "\n";
    
    // Check if it's serialized
    if (is_string($decrypted) && (strpos($decrypted, 's:') === 0 || strpos($decrypted, 'a:') === 0)) {
        echo "  ⚠️  Result is SERIALIZED PHP!\n";
        
        // Try to unserialize
        try {
            $unserialized = unserialize($decrypted);
            echo "  ✓ Unserialized successfully\n";
            echo "  Clean value: $unserialized\n\n";
            
            // This is the clean value we want
            echo "FIXING: Re-encrypting with clean value...\n";
            DB::table('companies')->where('id', $company->id)->update([
                'webhook_secret' => encrypt($unserialized)
            ]);
            echo "✓ webhook_secret fixed!\n\n";
            
        } catch (\Exception $e) {
            echo "  ✗ Unserialize failed: " . $e->getMessage() . "\n";
            echo "  Generating new secret...\n";
            $newSecret = 'whsec_' . bin2hex(random_bytes(32));
            DB::table('companies')->where('id', $company->id)->update([
                'webhook_secret' => encrypt($newSecret)
            ]);
            echo "  ✓ New secret: $newSecret\n\n";
        }
    } else {
        echo "  ✓ Value is clean (not serialized)\n\n";
    }
    
} catch (\Exception $e) {
    echo "✗ Decryption failed: " . $e->getMessage() . "\n";
    echo "Generating new secret...\n";
    $newSecret = 'whsec_' . bin2hex(random_bytes(32));
    DB::table('companies')->where('id', $company->id)->update([
        'webhook_secret' => encrypt($newSecret)
    ]);
    echo "✓ New secret: $newSecret\n\n";
}

echo "ANALYZING test_webhook_secret:\n";
echo "-------------------------------\n";

$rawValue = $company->test_webhook_secret;
echo "Raw DB value (first 100 chars): " . substr($rawValue, 0, 100) . "...\n";
echo "Length: " . strlen($rawValue) . " bytes\n\n";

try {
    $decrypted = decrypt($rawValue);
    echo "✓ First decryption successful\n";
    echo "  Type: " . gettype($decrypted) . "\n";
    echo "  Value: " . substr($decrypted, 0, 100) . "\n";
    
    if (is_string($decrypted) && (strpos($decrypted, 's:') === 0 || strpos($decrypted, 'a:') === 0)) {
        echo "  ⚠️  Result is SERIALIZED PHP!\n";
        
        try {
            $unserialized = unserialize($decrypted);
            echo "  ✓ Unserialized successfully\n";
            echo "  Clean value: $unserialized\n\n";
            
            echo "FIXING: Re-encrypting with clean value...\n";
            DB::table('companies')->where('id', $company->id)->update([
                'test_webhook_secret' => encrypt($unserialized)
            ]);
            echo "✓ test_webhook_secret fixed!\n\n";
            
        } catch (\Exception $e) {
            echo "  ✗ Unserialize failed: " . $e->getMessage() . "\n";
            echo "  Generating new secret...\n";
            $newSecret = 'whsec_test_' . bin2hex(random_bytes(32));
            DB::table('companies')->where('id', $company->id)->update([
                'test_webhook_secret' => encrypt($newSecret)
            ]);
            echo "  ✓ New secret: $newSecret\n\n";
        }
    } else {
        echo "  ✓ Value is clean (not serialized)\n\n";
    }
    
} catch (\Exception $e) {
    echo "✗ Decryption failed: " . $e->getMessage() . "\n";
    echo "Generating new secret...\n";
    $newSecret = 'whsec_test_' . bin2hex(random_bytes(32));
    DB::table('companies')->where('id', $company->id)->update([
        'test_webhook_secret' => encrypt($newSecret)
    ]);
    echo "✓ New secret: $newSecret\n\n";
}

echo "=== Fix Complete ===\n";
echo "\nNow run: php test_credentials_api.php\n";
