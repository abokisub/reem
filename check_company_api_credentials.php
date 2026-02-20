<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING COMPANY API CREDENTIALS ===\n\n";

// Get the company with business_id from the developer's report
$businessId = '3450968aa027e86e3ff5b0169dc17edd7694a846';

$company = DB::table('companies')->where('business_id', $businessId)->first();

if (!$company) {
    echo "❌ No company found with business_id: {$businessId}\n";
    echo "\nSearching for any companies...\n";
    
    $companies = DB::table('companies')->get();
    echo "Total companies: " . count($companies) . "\n\n";
    
    foreach ($companies as $comp) {
        echo "Company ID: {$comp->id}\n";
        echo "Name: {$comp->name}\n";
        echo "Business ID: {$comp->business_id}\n";
        echo "Status: {$comp->status}\n";
        echo str_repeat("-", 80) . "\n";
    }
    exit(1);
}

echo "✅ Company Found!\n\n";
echo "Company ID: {$company->id}\n";
echo "Name: {$company->name}\n";
echo "Business ID: {$company->business_id}\n";
echo "Status: {$company->status}\n";
echo "User ID: {$company->user_id}\n\n";

echo "=== LIVE CREDENTIALS ===\n";
echo "API Key: " . ($company->api_key ?? 'NULL') . "\n";
echo "Secret Key: " . ($company->api_secret_key ?? 'NULL') . "\n\n";

echo "=== TEST/SANDBOX CREDENTIALS ===\n";
echo "Test API Key: " . ($company->test_api_key ?? 'NULL') . "\n";
echo "Test Secret Key: " . ($company->test_secret_key ?? 'NULL') . "\n\n";

// Check if credentials match what developer is using
$devApiKey = '7db8dbb3991382487a1fc388a05d96a7139d92ba';
$devSecretKey = 'd8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c';

echo "=== CREDENTIAL MATCH CHECK ===\n";
echo "Developer's API Key matches LIVE: " . ($company->api_key === $devApiKey ? '✅ YES' : '❌ NO') . "\n";
echo "Developer's API Key matches TEST: " . ($company->test_api_key === $devApiKey ? '✅ YES' : '❌ NO') . "\n";
echo "Developer's Secret Key matches LIVE: " . ($company->api_secret_key === $devSecretKey ? '✅ YES' : '❌ NO') . "\n";
echo "Developer's Secret Key matches TEST: " . ($company->test_secret_key === $devSecretKey ? '✅ YES' : '❌ NO') . "\n\n";

// Check if API keys are encrypted
echo "=== ENCRYPTION CHECK ===\n";
if ($company->api_key && strlen($company->api_key) > 100) {
    echo "⚠️  API keys appear to be ENCRYPTED\n";
    echo "Length of stored API key: " . strlen($company->api_key) . " characters\n";
    echo "Length of developer's API key: " . strlen($devApiKey) . " characters\n\n";
    echo "This means the middleware needs to DECRYPT before comparing!\n";
} else {
    echo "✅ API keys appear to be plain text\n";
}

// Get user details
$user = DB::table('users')->where('id', $company->user_id)->first();
if ($user) {
    echo "\n=== USER DETAILS ===\n";
    echo "User ID: {$user->id}\n";
    echo "Name: {$user->name}\n";
    echo "Email: {$user->email}\n";
    echo "Type: {$user->type}\n";
}
