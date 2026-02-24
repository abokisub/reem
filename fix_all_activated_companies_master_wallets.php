<?php

/**
 * Fix All Activated Companies - Create Missing Master Wallets
 * 
 * This script:
 * 1. Finds all activated companies (is_active = 1)
 * 2. Checks if they have a company wallet
 * 3. Checks if they have a master virtual account
 * 4. Creates missing wallets and master accounts
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use App\Models\CompanyWallet;
use App\Models\VirtualAccount;
use App\Services\PalmPay\VirtualAccountService;

echo "========================================\n";
echo "Fix All Activated Companies\n";
echo "========================================\n\n";

// Get all activated companies (excluding admin company)
$companies = Company::where('is_active', true)
    ->where('email', '!=', 'admin@pointwave.com')
    ->get();

echo "Found " . $companies->count() . " activated companies\n\n";

$stats = [
    'total' => $companies->count(),
    'wallet_created' => 0,
    'master_account_created' => 0,
    'already_complete' => 0,
    'missing_kyc' => 0,
    'errors' => 0,
];

foreach ($companies as $company) {
    echo "----------------------------------------\n";
    echo "Company: {$company->name} (ID: {$company->id})\n";
    echo "Email: {$company->email}\n";
    
    // Check KYC
    $hasKyc = !empty($company->director_bvn) || 
              !empty($company->director_nin) || 
              !empty($company->business_registration_number);
    
    if (!$hasKyc) {
        echo "❌ SKIP: No KYC (missing director_bvn, director_nin, and RC number)\n";
        $stats['missing_kyc']++;
        continue;
    }
    
    echo "✅ KYC: ";
    if ($company->director_bvn) echo "Director BVN ✓ ";
    if ($company->director_nin) echo "Director NIN ✓ ";
    if ($company->business_registration_number) echo "RC Number ✓ ";
    echo "\n";
    
    // Check wallet
    $wallet = CompanyWallet::where('company_id', $company->id)->first();
    if (!$wallet) {
        echo "Creating company wallet...\n";
        try {
            $wallet = CompanyWallet::create([
                'company_id' => $company->id,
                'currency' => 'NGN',
                'balance' => 0,
                'ledger_balance' => 0,
                'pending_balance' => 0,
            ]);
            echo "✅ Wallet created (ID: {$wallet->id})\n";
            $stats['wallet_created']++;
        } catch (\Exception $e) {
            echo "❌ Wallet creation failed: " . $e->getMessage() . "\n";
            $stats['errors']++;
            continue;
        }
    } else {
        echo "✅ Wallet exists (ID: {$wallet->id}, Balance: ₦" . number_format($wallet->balance, 2) . ")\n";
    }
    
    // Check master virtual account
    $masterAccount = VirtualAccount::where('company_id', $company->id)
        ->where('is_master', 1)
        ->where('provider', 'pointwave')
        ->first();
    
    if (!$masterAccount) {
        echo "Creating master virtual account...\n";
        try {
            $virtualAccountService = new VirtualAccountService();
            
            $virtualAccount = $virtualAccountService->createVirtualAccount(
                $company->id,
                'company_master_' . $company->id,
                [
                    'name' => $company->name,
                    'email' => $company->email,
                    'phone' => $company->phone,
                    'account_type' => 'static',
                ],
                '100033',
                null
            );
            
            // Mark as master account
            $virtualAccount->update([
                'is_master' => true,
                'provider' => 'pointwave',
            ]);
            
            echo "✅ Master account created\n";
            echo "   Account Number: {$virtualAccount->account_number}\n";
            echo "   Account Name: {$virtualAccount->account_name}\n";
            echo "   Bank: {$virtualAccount->bank_name}\n";
            echo "   KYC Source: {$virtualAccount->kyc_source}\n";
            $stats['master_account_created']++;
            
        } catch (\Exception $e) {
            echo "❌ Master account creation failed: " . $e->getMessage() . "\n";
            $stats['errors']++;
            continue;
        }
    } else {
        echo "✅ Master account exists\n";
        echo "   Account Number: {$masterAccount->account_number}\n";
        echo "   Account Name: {$masterAccount->account_name}\n";
        echo "   Bank: {$masterAccount->bank_name}\n";
        $stats['already_complete']++;
    }
}

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Total Companies: {$stats['total']}\n";
echo "Wallets Created: {$stats['wallet_created']}\n";
echo "Master Accounts Created: {$stats['master_account_created']}\n";
echo "Already Complete: {$stats['already_complete']}\n";
echo "Missing KYC (Skipped): {$stats['missing_kyc']}\n";
echo "Errors: {$stats['errors']}\n";
echo "========================================\n";
