<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    KOBOPOINT PALMPAY ISSUE DIAGNOSIS                        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$businessId = '3450968aa027e86e3ff5b0169dc17edd7694a846';

echo "🔍 Checking Kobopoint Configuration\n";
echo str_repeat("-", 60) . "\n\n";

// Find company
$company = DB::table('companies')->where('business_id', $businessId)->first();

if (!$company) {
    echo "❌ Company not found with Business ID: {$businessId}\n";
    exit(1);
}

echo "✅ Company Found: {$company->name}\n";
echo "   Email: {$company->email}\n";
echo "   Status: {$company->status}\n\n";

// Test virtual account creation with actual service
echo "🧪 TESTING VIRTUAL ACCOUNT CREATION\n";
echo str_repeat("-", 60) . "\n\n";

try {
    $service = new \App\Services\PalmPay\VirtualAccountService();
    
    $testData = [
        'name' => 'Test Customer Diagnostic',
        'email' => 'diagnostic@test.com',
        'phone' => '+2349012345678',
        'account_type' => 'static',
        'external_reference' => 'DIAG-' . time()
    ];
    
    echo "Creating test virtual account...\n";
    echo "Customer: {$testData['name']}\n";
    echo "Email: {$testData['email']}\n";
    echo "Phone: {$testData['phone']}\n\n";
    
    $result = $service->createVirtualAccount(
        $company->id,
        'DIAG-' . time(),
        $testData,
        '100033'
    );
    
    echo "✅ VIRTUAL ACCOUNT CREATED SUCCESSFULLY!\n";
    echo str_repeat("-", 60) . "\n";
    echo "Account Number: {$result->palmpay_account_number}\n";
    echo "Account Name: {$result->palmpay_account_name}\n";
    echo "Bank: {$result->palmpay_bank_name}\n";
    echo "Status: {$result->status}\n\n";
    
    echo "🎉 KOBOPOINT INTEGRATION IS WORKING!\n";
    echo str_repeat("-", 60) . "\n";
    echo "The developer can now create virtual accounts successfully.\n";
    echo "No configuration changes needed.\n\n";
    
    // Clean up test account
    echo "🧹 Cleaning up test account...\n";
    DB::table('virtual_accounts')->where('id', $result->id)->delete();
    echo "✅ Test account removed\n\n";
    
} catch (\Exception $e) {
    echo "❌ VIRTUAL ACCOUNT CREATION FAILED\n";
    echo str_repeat("-", 60) . "\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    
    $errorMsg = $e->getMessage();
    
    if (str_contains($errorMsg, 'OPEN_GW_000008') || str_contains($errorMsg, 'sign error')) {
        echo "🔍 DIAGNOSIS: Signature Error\n";
        echo str_repeat("-", 60) . "\n";
        echo "This means PalmPay cannot verify the API signature.\n\n";
        echo "Possible causes:\n";
        echo "  1. ❌ Incorrect PALMPAY_PRIVATE_KEY in .env\n";
        echo "  2. ❌ Incorrect PALMPAY_PUBLIC_KEY in .env\n";
        echo "  3. ❌ Incorrect PALMPAY_MERCHANT_ID or PALMPAY_APP_ID\n";
        echo "  4. ❌ Keys not activated by PalmPay\n\n";
        
    } elseif (str_contains($errorMsg, 'OPEN_GW_000022') || str_contains($errorMsg, 'invalid url router')) {
        echo "🔍 DIAGNOSIS: Invalid URL Router\n";
        echo str_repeat("-", 60) . "\n";
        echo "This means the API endpoint path is incorrect.\n\n";
        echo "Current base URL: " . config('services.palmpay.base_url') . "\n";
        echo "Expected: https://open-gw-prod.palmpay-inc.com\n\n";
        echo "This is likely a PalmPay API version issue.\n";
        echo "The endpoint structure may have changed.\n\n";
        
    } elseif (str_contains($errorMsg, 'No KYC available')) {
        echo "🔍 DIAGNOSIS: Missing KYC Information\n";
        echo str_repeat("-", 60) . "\n";
        echo "Company needs director BVN/NIN or customer needs to provide BVN/NIN.\n\n";
        echo "Current company KYC:\n";
        echo "  Director BVN: " . ($company->director_bvn ?? 'NOT SET') . "\n";
        echo "  Director NIN: " . ($company->director_nin ?? 'NOT SET') . "\n";
        echo "  RC Number: " . ($company->business_registration_number ?? 'NOT SET') . "\n\n";
        
    } else {
        echo "🔍 DIAGNOSIS: Unknown Error\n";
        echo str_repeat("-", 60) . "\n";
        echo "Please check the error message above for details.\n\n";
    }
    
    echo "📋 SYSTEM CONFIGURATION\n";
    echo str_repeat("-", 60) . "\n";
    echo "PALMPAY_BASE_URL: " . config('services.palmpay.base_url') . "\n";
    echo "PALMPAY_MERCHANT_ID: " . config('services.palmpay.merchant_id') . "\n";
    echo "PALMPAY_APP_ID: " . config('services.palmpay.app_id') . "\n";
    echo "PALMPAY_PUBLIC_KEY: " . (config('services.palmpay.public_key') ? 'SET' : 'NOT SET') . "\n";
    echo "PALMPAY_PRIVATE_KEY: " . (config('services.palmpay.private_key') ? 'SET' : 'NOT SET') . "\n\n";
    
    echo "📧 NEXT STEPS\n";
    echo str_repeat("-", 60) . "\n";
    echo "1. Check Laravel logs: tail -f storage/logs/laravel.log\n";
    echo "2. Contact PalmPay support with the error code\n";
    echo "3. Verify API credentials are for production environment\n";
    echo "4. Check if PalmPay account is fully activated\n\n";
    
    exit(1);
}

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    DIAGNOSIS COMPLETE                                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
