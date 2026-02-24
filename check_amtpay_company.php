<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "Check Amtpay Company Status\n";
echo "========================================\n\n";

// Find amtpay company
$company = DB::table('companies')
    ->where('email', 'amtpxon@gmail.com')
    ->orWhere('name', 'like', '%amtpay%')
    ->first();

if (!$company) {
    echo "❌ Company 'amtpay' not found\n";
    echo "\nSearching all recent companies...\n";
    $recentCompanies = DB::table('companies')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get(['id', 'name', 'email', 'kyc_status', 'created_at']);
    
    foreach ($recentCompanies as $c) {
        echo "  [{$c->id}] {$c->name} ({$c->email}) - {$c->kyc_status} - {$c->created_at}\n";
    }
    exit(1);
}

echo "Company Found:\n";
echo "  ID: {$company->id}\n";
echo "  Name: {$company->name}\n";
echo "  Email: {$company->email}\n";
echo "  KYC Status: {$company->kyc_status}\n";
echo "  Is Active: " . ($company->is_active ? 'Yes' : 'No') . "\n";
echo "  Status: {$company->status}\n\n";

// Check company wallet
$wallet = DB::table('company_wallets')
    ->where('company_id', $company->id)
    ->first();

if ($wallet) {
    echo "✅ Company Wallet EXISTS\n";
    echo "  Wallet ID: {$wallet->id}\n";
    echo "  Balance: ₦{$wallet->balance}\n";
    echo "  Currency: {$wallet->currency}\n\n";
} else {
    echo "❌ Company Wallet MISSING\n\n";
}

// Check virtual accounts
$virtualAccounts = DB::table('virtual_accounts')
    ->where('company_id', $company->id)
    ->where('is_master', 1)
    ->get();

echo "Master Virtual Accounts:\n";
if ($virtualAccounts->count() > 0) {
    foreach ($virtualAccounts as $va) {
        echo "  ✅ {$va->provider}: {$va->account_number} ({$va->bank_name})\n";
    }
} else {
    echo "  ❌ NO MASTER ACCOUNTS FOUND\n";
}
echo "\n";

// Check all virtual accounts for this company
$allAccounts = DB::table('virtual_accounts')
    ->where('company_id', $company->id)
    ->get();

echo "All Virtual Accounts ({$allAccounts->count()}):\n";
foreach ($allAccounts as $va) {
    $masterFlag = $va->is_master ? '[MASTER]' : '[CUSTOMER]';
    echo "  {$masterFlag} {$va->provider}: {$va->account_number} - {$va->account_name}\n";
}
echo "\n";

// Check company BVN
echo "Company KYC Info:\n";
echo "  Director BVN: " . ($company->director_bvn ?? 'NOT SET') . "\n";
echo "  CAC Number: " . ($company->cac_number ?? 'NOT SET') . "\n";
echo "  Business Type: " . ($company->business_type ?? 'NOT SET') . "\n";
echo "\n";

echo "========================================\n";
echo "DIAGNOSIS:\n";
echo "========================================\n";

if (!$wallet) {
    echo "❌ CRITICAL: Company wallet missing - needs to be created\n";
}

if ($virtualAccounts->count() == 0) {
    echo "❌ CRITICAL: No master virtual account - needs to be created\n";
    if (!$company->director_bvn) {
        echo "   Reason: Director BVN not submitted\n";
        echo "   Solution: Company must submit KYC with director BVN\n";
    } else {
        echo "   Director BVN exists: {$company->director_bvn}\n";
        echo "   Solution: Manually create master account\n";
    }
}

if ($company->kyc_status != 'approved' && $company->kyc_status != 'verified') {
    echo "⚠️  WARNING: KYC status is '{$company->kyc_status}' - should be 'approved' or 'verified'\n";
}
