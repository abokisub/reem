<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "Fix ALL Activated Companies\n";
echo "========================================\n\n";

// Find all activated companies (verified or approved KYC status)
$companies = DB::table('companies')
    ->whereIn('kyc_status', ['verified', 'approved'])
    ->where('is_active', 1)
    ->get();

echo "Found " . $companies->count() . " activated company(ies)\n\n";

foreach ($companies as $company) {
    echo "----------------------------------------\n";
    echo "Company: {$company->name} (ID: {$company->id})\n";
    echo "Email: {$company->email}\n";
    echo "KYC Status: {$company->kyc_status}\n";
    
    // Check company wallet
    $wallet = DB::table('company_wallets')
        ->where('company_id', $company->id)
        ->first();
    
    if (!$wallet) {
        echo "  ⚠️  Creating company wallet...\n";
        DB::table('company_wallets')->insert([
            'company_id' => $company->id,
            'currency' => 'NGN',
            'balance' => 0,
            'ledger_balance' => 0,
            'pending_balance' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "  ✅ Company wallet created\n";
    } else {
        echo "  ✅ Company wallet exists\n";
    }
    
    // Check master virtual account
    $masterAccount = DB::table('virtual_accounts')
        ->where('company_id', $company->id)
        ->where('is_master', 1)
        ->where('provider', 'pointwave')
        ->first();
    
    if ($masterAccount) {
        echo "  ✅ Master account exists: {$masterAccount->account_number}\n";
    } else {
        echo "  ⚠️  Master account missing\n";
        
        // Check if company has director BVN
        if (!$company->director_bvn) {
            echo "  ⚠️  Director BVN missing - using placeholder\n";
            // Use a placeholder BVN for now - company can update later
            $placeholderBVN = '22222222222'; // Placeholder
            
            DB::table('companies')
                ->where('id', $company->id)
                ->update(['director_bvn' => $placeholderBVN]);
            
            $company->director_bvn = $placeholderBVN;
            echo "  ℹ️  Set placeholder BVN: {$placeholderBVN}\n";
        }
        
        // Create master virtual account
        try {
            $virtualAccountService = new \App\Services\PalmPay\VirtualAccountService();
            
            $result = $virtualAccountService->createVirtualAccount(
                $company->id,
                null, // No customer_id for master account
                $company->name,
                $company->email,
                $company->phone,
                $company->director_bvn,
                null, // No NIN
                true  // is_master = true
            );
            
            if ($result['success']) {
                echo "  ✅ Master account created: {$result['account_number']}\n";
            } else {
                echo "  ❌ Failed: {$result['message']}\n";
            }
        } catch (\Exception $e) {
            echo "  ❌ Exception: {$e->getMessage()}\n";
        }
    }
    
    echo "\n";
}

echo "========================================\n";
echo "✅ Process Complete!\n";
echo "========================================\n";
