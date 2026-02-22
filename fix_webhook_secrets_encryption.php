<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Fixing Webhook Secrets Encryption ===\n\n";

// Get all companies with webhook secrets
$companies = DB::table('companies')
    ->whereNotNull('webhook_secret')
    ->orWhereNotNull('test_webhook_secret')
    ->get();

echo "Found " . $companies->count() . " companies with webhook secrets\n\n";

foreach ($companies as $company) {
    echo "Processing Company ID: {$company->id} ({$company->name})\n";
    
    $updates = [];
    
    // Try to decrypt and re-encrypt webhook_secret
    if ($company->webhook_secret) {
        try {
            $decrypted = decrypt($company->webhook_secret);
            echo "  ✓ webhook_secret decrypted successfully\n";
            
            // Re-encrypt with current APP_KEY
            $updates['webhook_secret'] = encrypt($decrypted);
        } catch (\Exception $e) {
            echo "  ✗ webhook_secret decryption failed: " . $e->getMessage() . "\n";
            echo "  → Generating new webhook_secret\n";
            
            $newSecret = 'whsec_' . bin2hex(random_bytes(32));
            $updates['webhook_secret'] = encrypt($newSecret);
            echo "  → New secret: $newSecret\n";
        }
    } else {
        echo "  → No webhook_secret found, generating new one\n";
        $newSecret = 'whsec_' . bin2hex(random_bytes(32));
        $updates['webhook_secret'] = encrypt($newSecret);
        echo "  → New secret: $newSecret\n";
    }
    
    // Try to decrypt and re-encrypt test_webhook_secret
    if ($company->test_webhook_secret) {
        try {
            $decrypted = decrypt($company->test_webhook_secret);
            echo "  ✓ test_webhook_secret decrypted successfully\n";
            
            // Re-encrypt with current APP_KEY
            $updates['test_webhook_secret'] = encrypt($decrypted);
        } catch (\Exception $e) {
            echo "  ✗ test_webhook_secret decryption failed: " . $e->getMessage() . "\n";
            echo "  → Generating new test_webhook_secret\n";
            
            $newSecret = 'whsec_test_' . bin2hex(random_bytes(32));
            $updates['test_webhook_secret'] = encrypt($newSecret);
            echo "  → New secret: $newSecret\n";
        }
    } else {
        echo "  → No test_webhook_secret found, generating new one\n";
        $newSecret = 'whsec_test_' . bin2hex(random_bytes(32));
        $updates['test_webhook_secret'] = encrypt($newSecret);
        echo "  → New secret: $newSecret\n";
    }
    
    // Update the database
    if (!empty($updates)) {
        DB::table('companies')->where('id', $company->id)->update($updates);
        echo "  ✓ Database updated\n";
    }
    
    echo "\n";
}

echo "=== Fix Complete ===\n";
echo "\nNOTE: If you generated new secrets, you'll need to update your webhook configurations.\n";
