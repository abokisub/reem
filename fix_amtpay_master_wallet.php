<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "Fix Amtpay Master Wallet & Account\n";
echo "========================================\n\n";

// Find amtpay company
$company = DB::table('companies')
    ->where('email', 'amtpxon@gmail.com')
    ->orWhere('name', 'like', '%amtpay%')
    ->first();

if (!$company) {
    echo "❌ Company 'amtpay' not found\n";
    exit(1);
}

echo "Company: {$company->name} (ID: {$company->id})\n";
echo "Email: {$company->email}\n";
echo "KYC Status: {$company->kyc_status}\n\n";

// Step 1: Check/Create Company Wallet
echo "Step 1: Checking Company Wallet...\n";
$wallet = DB::table('company_wallets')
    ->where('company_id', $company->id)
    ->first();

if (!$wallet) {
    echo "  Creating company wallet...\n";
    DB::table('company_wallets')->insert([
        'company_id' => $company->id,
        'currency' => 'NGN',
        'balance' => 0,
        'ledger_balance' => 0,
        'pending_balance' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "  ✅ Company wallet created\n\n";
} else {
    echo "  ✅ Company wallet already exists (ID: {$wallet->id})\n\n";
}

// Step 2: Check if master account exists
echo "Step 2: Checking Master Virtual Account...\n";
$masterAccount = DB::table('virtual_accounts')
    ->where('company_id', $company->id)
    ->where('is_master', 1)
    ->where('provider', 'pointwave')
    ->first();

if ($masterAccount) {
    echo "  ✅ Master account already exists: {$masterAccount->account_number}\n";
    exit(0);
}

// Step 3: Check if company has director BVN
echo "Step 3: Checking Director BVN...\n";
if (!$company->director_bvn) {
    echo "  ❌ Director BVN not found\n";
    echo "\n";
    echo "========================================\n";
    echo "CANNOT CREATE MASTER ACCOUNT\n";
    echo "========================================\n";
    echo "Reason: Company has not submitted director BVN\n";
    echo "\n";
    echo "Solution:\n";
    echo "1. Company must login to dashboard\n";
    echo "2. Go to KYC section\n";
    echo "3. Submit director BVN information\n";
    echo "4. Admin approves the KYC\n";
    echo "5. Then master account will be auto-created\n";
    echo "\n";
    echo "OR you can manually set director BVN in database:\n";
    echo "UPDATE companies SET director_bvn = '12345678901' WHERE id = {$company->id};\n";
    exit(1);
}

echo "  ✅ Director BVN found: {$company->director_bvn}\n\n";

// Step 4: Create master virtual account using PalmPay
echo "Step 4: Creating Master Virtual Account...\n";
try {
    $virtualAccountService = new \App\Services\PalmPay\VirtualAccountService();
    
    // Create master account for the company
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
        echo "  ✅ Master account created successfully!\n";
        echo "  Account Number: {$result['account_number']}\n";
        echo "  Bank: {$result['bank_name']}\n";
        echo "  Account Name: {$result['account_name']}\n";
        echo "\n";
        echo "========================================\n";
        echo "✅ SUCCESS!\n";
        echo "========================================\n";
        echo "Company '{$company->name}' now has:\n";
        echo "  ✅ Company wallet\n";
        echo "  ✅ Master virtual account\n";
        echo "\n";
        echo "Customers can now create virtual accounts!\n";
    } else {
        echo "  ❌ Failed to create master account\n";
        echo "  Error: {$result['message']}\n";
        echo "\n";
        echo "This might be because:\n";
        echo "  - Director BVN is invalid\n";
        echo "  - PalmPay API is down\n";
        echo "  - Company already has too many accounts\n";
        echo "\n";
        echo "Check logs for more details:\n";
        echo "  tail -f storage/logs/laravel.log\n";
    }
} catch (\Exception $e) {
    echo "  ❌ Exception: {$e->getMessage()}\n";
    echo "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString();
}
