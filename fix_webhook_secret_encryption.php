<?php

/**
 * Fix Webhook Secret Encryption for Kobopoint
 * 
 * The webhook_secret is stored with Laravel's encrypted cast,
 * but it might be corrupted or double-encrypted.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;

echo "\n";
echo "================================================================================\n";
echo "FIX WEBHOOK SECRET ENCRYPTION\n";
echo "================================================================================\n";
echo "\n";

// Get Kobopoint company
$company = Company::find(4);

if (!$company) {
    echo "❌ Company not found!\n\n";
    exit(1);
}

echo "Company: {$company->name}\n";
echo "\n";

// The correct plain secret (from Kobopoint's .env)
$correctSecret = 'whsec_aad2319b0eb134b2f598ad63d4c2a7a2a4b054f42781b6599b92a7cc1a97fc68';

echo "Current webhook_secret (decrypted): ";
try {
    $current = $company->webhook_secret;
    echo $current . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

echo "Correct secret should be: {$correctSecret}\n";
echo "\n";

// Check if they match
if ($company->webhook_secret === $correctSecret) {
    echo "✅ Webhook secret is correct! No fix needed.\n";
    echo "\n";
    echo "The issue might be elsewhere. Check:\n";
    echo "1. Kobopoint's .env has the correct secret\n";
    echo "2. Kobopoint ran: php artisan config:clear\n";
    echo "3. Both systems are using the same JSON payload\n";
    echo "\n";
    exit(0);
}

echo "❌ Webhook secret is WRONG!\n";
echo "\n";
echo "Do you want to fix it? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'yes') {
    echo "\n❌ Fix cancelled.\n\n";
    exit(0);
}

echo "\n";
echo "Updating webhook_secret...\n";

// Update with the correct secret
// Laravel will automatically encrypt it when saving
$company->webhook_secret = $correctSecret;
$company->save();

echo "✅ Webhook secret updated!\n";
echo "\n";

// Verify
$company->refresh();
$verified = $company->webhook_secret;

if ($verified === $correctSecret) {
    echo "✅ VERIFIED: Webhook secret is now correct!\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Retry failed webhooks: php retry_failed_company_webhooks.php\n";
    echo "2. Check status: php check_company_webhook_logs.php\n";
    echo "3. All webhooks should now succeed with HTTP 200\n";
} else {
    echo "❌ VERIFICATION FAILED!\n";
    echo "Expected: {$correctSecret}\n";
    echo "Got: {$verified}\n";
}

echo "\n";
echo "================================================================================\n";
echo "\n";
