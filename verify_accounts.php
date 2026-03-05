<?php
// Quick verification script to check recent virtual accounts
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VirtualAccount;
use App\Models\Company;

echo "🔍 VIRTUAL ACCOUNTS VERIFICATION\n";
echo "===============================\n\n";

// Get Company 4 info
$company = Company::find(4);
echo "📋 COMPANY INFO:\n";
echo "- Name: {$company->name}\n";
echo "- Director BVN: " . ($company->director_bvn ?? 'Not Set') . "\n";
echo "- Director NIN: " . ($company->director_nin ?? 'Not Set') . "\n\n";

// Get recent accounts (last 24 hours)
echo "📊 RECENT ACCOUNTS (LAST 24 HOURS):\n";
$recentAccounts = VirtualAccount::where('company_id', 4)
    ->where('created_at', '>=', now()->subDay())
    ->orderBy('created_at', 'desc')
    ->get();

if ($recentAccounts->count() > 0) {
    foreach ($recentAccounts as $account) {
        echo "- Account: {$account->account_number}\n";
        echo "  Customer: {$account->customer_name}\n";
        echo "  KYC: {$account->kyc_source}\n";
        echo "  Created: {$account->created_at}\n\n";
    }
} else {
    echo "No accounts created in the last 24 hours\n\n";
}

// Get total account count
echo "📈 STATISTICS:\n";
$totalAccounts = VirtualAccount::where('company_id', 4)->count();
$activeAccounts = VirtualAccount::where('company_id', 4)
    ->where('status', 'active')
    ->whereNull('deleted_at')
    ->count();

echo "- Total accounts: $totalAccounts\n";
echo "- Active accounts: $activeAccounts\n";

// KYC method breakdown
$kycBreakdown = VirtualAccount::where('company_id', 4)
    ->selectRaw('kyc_source, COUNT(*) as count')
    ->groupBy('kyc_source')
    ->get();

echo "- KYC Methods Used:\n";
foreach ($kycBreakdown as $kyc) {
    echo "  * {$kyc->kyc_source}: {$kyc->count} accounts\n";
}

echo "\n✅ VERIFICATION COMPLETED\n";