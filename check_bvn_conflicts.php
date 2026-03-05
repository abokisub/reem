<?php
// Check if the director BVN is being used by other companies
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\VirtualAccount;

echo "🔍 CHECKING BVN CONFLICTS\n";
echo "========================\n\n";

$problematicBvn = '22490148602';

echo "📋 COMPANIES USING BVN: $problematicBvn\n";
$companiesWithSameBvn = Company::where('director_bvn', $problematicBvn)->get();

echo "Found " . $companiesWithSameBvn->count() . " companies using this BVN:\n";
foreach ($companiesWithSameBvn as $company) {
    echo "- Company ID: {$company->id}\n";
    echo "  Name: {$company->name}\n";
    echo "  Director BVN: {$company->director_bvn}\n";
    echo "  KYC Status: " . ($company->kyc_status ?? 'Not Set') . "\n";
    echo "  Created: {$company->created_at}\n\n";
}

echo "📋 VIRTUAL ACCOUNTS USING THIS BVN:\n";
$accountsWithBvn = VirtualAccount::where('director_bvn', $problematicBvn)
    ->orWhere('bvn', $problematicBvn)
    ->get();

echo "Found " . $accountsWithBvn->count() . " virtual accounts using this BVN:\n";
foreach ($accountsWithBvn as $account) {
    echo "- Account: {$account->account_number}\n";
    echo "  Customer: {$account->customer_name}\n";
    echo "  Company: {$account->company_id}\n";
    echo "  KYC Source: {$account->kyc_source}\n";
    echo "  Director BVN: " . ($account->director_bvn ?? 'None') . "\n";
    echo "  Customer BVN: " . ($account->bvn ?? 'None') . "\n";
    echo "  Created: {$account->created_at}\n\n";
}

echo "🔍 ANALYSIS:\n";
if ($companiesWithSameBvn->count() > 1) {
    echo "⚠️  MULTIPLE COMPANIES using same director BVN!\n";
    echo "This could cause PalmPay to reject the BVN as 'duplicate'\n";
} else {
    echo "✅ Only one company using this BVN\n";
}

if ($accountsWithBvn->count() > 0) {
    echo "✅ BVN has been used successfully before\n";
    echo "Recent PalmPay rejection suggests a new issue\n";
} else {
    echo "❌ No virtual accounts found using this BVN\n";
    echo "This BVN might be problematic from the start\n";
}

echo "\n💡 RECOMMENDATIONS:\n";
echo "1. Contact PalmPay support about BVN: $problematicBvn\n";
echo "2. Ask why this BVN is being rejected as 'duplicate'\n";
echo "3. Request BVN status check from PalmPay\n";
echo "4. Consider using director NIN as alternative: 35257106066\n";
echo "5. Test with a different director BVN if available\n\n";

echo "🚨 IMMEDIATE ACTION NEEDED:\n";
echo "Contact PalmPay support with:\n";
echo "- Company: kobopoint\n";
echo "- Director BVN: $problematicBvn\n";
echo "- Error: licenseNumber duplicate (Code: AC100009)\n";
echo "- Request: BVN status verification and resolution\n";