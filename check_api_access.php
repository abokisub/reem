<?php

/**
 * Check API Access Status for a Company
 * 
 * This script checks why a company's API access might be locked
 * 
 * Usage: php check_api_access.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;

echo "=== API Access Diagnostic Tool ===\n\n";

// Get company name from user
echo "Enter company name (e.g., Amtpay): ";
$companyName = trim(fgets(STDIN));

if (empty($companyName)) {
    echo "Error: Company name is required\n";
    exit(1);
}

// Find company
$company = Company::where('name', 'LIKE', "%{$companyName}%")->first();

if (!$company) {
    echo "Error: Company not found\n";
    echo "\nAvailable companies:\n";
    $companies = Company::select('id', 'name')->get();
    foreach ($companies as $c) {
        echo "  - ID: {$c->id} | Name: {$c->name}\n";
    }
    exit(1);
}

echo "\n=== Company Details ===\n";
echo "ID: {$company->id}\n";
echo "Name: {$company->name}\n";
echo "Business ID: {$company->business_id}\n";
echo "\n=== API Access Status ===\n";
echo "status field: {$company->status}\n";
echo "is_active field: " . ($company->is_active ? 'true (1)' : 'false (0)') . "\n";
echo "isActive() method: " . ($company->isActive() ? 'PASS ✓' : 'FAIL ✗') . "\n";

echo "\n=== KYC Status ===\n";
echo "kyc_status: {$company->kyc_status}\n";

echo "\n=== API Keys ===\n";
echo "Live Public Key: " . substr($company->api_public_key, 0, 20) . "...\n";
echo "Live Secret Key: " . (strlen($company->api_secret_key) > 0 ? 'Set ✓' : 'Not Set ✗') . "\n";
echo "Test Public Key: " . substr($company->test_public_key, 0, 20) . "...\n";
echo "Test Secret Key: " . (strlen($company->test_secret_key) > 0 ? 'Set ✓' : 'Not Set ✗') . "\n";

echo "\n=== Diagnosis ===\n";
if (!$company->isActive()) {
    echo "❌ API ACCESS IS LOCKED\n\n";
    echo "Reasons:\n";
    if ($company->status !== 'active') {
        echo "  - status is '{$company->status}' (must be 'active')\n";
    }
    if (!$company->is_active) {
        echo "  - is_active is false (must be true)\n";
    }
    
    echo "\n=== Solution ===\n";
    echo "To unlock API access, run:\n\n";
    echo "UPDATE companies SET status = 'active', is_active = 1 WHERE id = {$company->id};\n\n";
    echo "Or use this PHP command:\n";
    echo "php artisan tinker --execute=\"\$c = \\App\\Models\\Company::find({$company->id}); \$c->status = 'active'; \$c->is_active = true; \$c->save(); echo 'API unlocked';\"\n";
} else {
    echo "✅ API ACCESS IS UNLOCKED\n";
    echo "The company can make API calls successfully.\n";
}

echo "\n=== Recent Virtual Accounts ===\n";
$virtualAccounts = \App\Models\VirtualAccount::where('company_id', $company->id)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get(['id', 'account_number', 'account_name', 'status', 'created_at']);

if ($virtualAccounts->count() > 0) {
    foreach ($virtualAccounts as $va) {
        echo "  - {$va->account_number} | {$va->account_name} | Status: {$va->status} | Created: {$va->created_at}\n";
    }
} else {
    echo "  No virtual accounts found\n";
}

echo "\n";
