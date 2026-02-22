<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use Illuminate\Support\Facades\DB;

// Get Kobopoint company (ID 4)
$companyId = 4;

echo "=== Debugging Webhook Secrets for Company ID: $companyId ===\n\n";

// Get raw data from database
$rawCompany = DB::table('companies')->where('id', $companyId)->first();

echo "Raw Database Values:\n";
echo "- webhook_secret (encrypted): " . ($rawCompany->webhook_secret ? 'EXISTS (' . strlen($rawCompany->webhook_secret) . ' chars)' : 'NULL') . "\n";
echo "- test_webhook_secret (encrypted): " . ($rawCompany->test_webhook_secret ? 'EXISTS (' . strlen($rawCompany->test_webhook_secret) . ' chars)' : 'NULL') . "\n";
echo "\n";

// Try to get via Eloquent model
$company = Company::find($companyId);

echo "Eloquent Model Access:\n";
try {
    $webhookSecret = $company->webhook_secret;
    echo "- webhook_secret: " . ($webhookSecret ?: 'EMPTY') . "\n";
} catch (\Exception $e) {
    echo "- webhook_secret ERROR: " . $e->getMessage() . "\n";
}

try {
    $testWebhookSecret = $company->test_webhook_secret;
    echo "- test_webhook_secret: " . ($testWebhookSecret ?: 'EMPTY') . "\n";
} catch (\Exception $e) {
    echo "- test_webhook_secret ERROR: " . $e->getMessage() . "\n";
}

echo "\n";
echo "Other Credentials:\n";
echo "- business_id: " . $company->business_id . "\n";
echo "- api_public_key: " . $company->api_public_key . "\n";
echo "- webhook_url: " . ($company->webhook_url ?: 'NULL') . "\n";
echo "- test_webhook_url: " . ($company->test_webhook_url ?: 'NULL') . "\n";

echo "\n=== End Debug ===\n";
