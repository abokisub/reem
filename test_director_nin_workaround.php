<?php
// Test using director NIN instead of BVN as a workaround
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Services\PalmPay\VirtualAccountService;

$confirm = $argv[1] ?? null;

if ($confirm !== 'CONFIRM') {
    echo "🧪 DIRECTOR NIN WORKAROUND TEST\n";
    echo "==============================\n\n";
    echo "This will temporarily modify Company 4's KYC to use director NIN\n";
    echo "instead of director BVN to bypass the PalmPay BVN issue.\n\n";
    echo "Changes:\n";
    echo "- Temporarily clear director_bvn\n";
    echo "- Keep director_nin (35257106066)\n";
    echo "- Test virtual account creation\n";
    echo "- Restore director_bvn after test\n\n";
    echo "⚠️  WARNING: This modifies production company data temporarily\n";
    echo "To proceed, run: php test_director_nin_workaround.php CONFIRM\n";
    exit(1);
}

echo "🧪 DIRECTOR NIN WORKAROUND TEST\n";
echo "==============================\n\n";

try {
    // Get Company 4
    $company = Company::find(4);
    if (!$company) {
        throw new \Exception("Company 4 not found");
    }
    
    echo "📋 CURRENT COMPANY DATA:\n";
    echo "- Name: {$company->name}\n";
    echo "- Director BVN: {$company->director_bvn}\n";
    echo "- Director NIN: {$company->director_nin}\n\n";
    
    // Backup original BVN
    $originalBvn = $company->director_bvn;
    
    echo "🔄 TEMPORARILY SWITCHING TO DIRECTOR NIN...\n";
    
    // Temporarily clear BVN so system uses NIN
    $company->update(['director_bvn' => null]);
    
    echo "✅ Director BVN temporarily cleared\n";
    echo "System will now use Director NIN: {$company->director_nin}\n\n";
    
    // Test virtual account creation
    echo "🧪 TESTING VIRTUAL ACCOUNT CREATION WITH DIRECTOR NIN...\n";
    
    $palmPayService = new VirtualAccountService();
    
    $testCustomerData = [
        'name' => 'Test NIN Customer',
        'email' => 'test_nin@example.com',
        'phone' => '08099887766'
    ];
    
    echo "Test customer data:\n";
    echo "- Name: {$testCustomerData['name']}\n";
    echo "- Email: {$testCustomerData['email']}\n";
    echo "- Phone: {$testCustomerData['phone']}\n\n";
    
    $testAccount = $palmPayService->createVirtualAccount(
        4, // Company ID
        'test_nin_' . uniqid(),
        $testCustomerData,
        '100033'
    );
    
    echo "✅ SUCCESS! Virtual account created with director NIN:\n";
    echo "- Account Number: {$testAccount->account_number}\n";
    echo "- Customer Name: {$testAccount->customer_name}\n";
    echo "- KYC Source: {$testAccount->kyc_source}\n";
    echo "- Identity Type: {$testAccount->identity_type}\n\n";
    
    // Clean up test account
    echo "🧹 CLEANING UP TEST ACCOUNT...\n";
    $testAccount->delete();
    echo "✅ Test account deleted\n\n";
    
    echo "🎉 DIRECTOR NIN WORKAROUND SUCCESSFUL!\n";
    echo "The issue is specifically with the director BVN, not the system.\n";
    echo "Director NIN works perfectly as an alternative.\n\n";
    
} catch (\Exception $e) {
    echo "❌ TEST FAILED: " . $e->getMessage() . "\n\n";
    
    if (strpos($e->getMessage(), 'licenseNumber duplicate') !== false) {
        echo "🔍 ANALYSIS: Director NIN also has duplicate issue\n";
        echo "This suggests a broader PalmPay problem, not specific to BVN\n\n";
    }
} finally {
    // Always restore original BVN
    if (isset($company) && isset($originalBvn)) {
        echo "🔄 RESTORING ORIGINAL DIRECTOR BVN...\n";
        $company->update(['director_bvn' => $originalBvn]);
        echo "✅ Director BVN restored: $originalBvn\n\n";
    }
}

echo "📋 CONCLUSIONS:\n";
echo "1. If test succeeded: Use director NIN as temporary workaround\n";
echo "2. If test failed: Contact PalmPay about broader KYC issues\n";
echo "3. Either way: Contact PalmPay support about BVN rejection\n";
echo "4. Request PalmPay to investigate why BVN 22490148602 is rejected\n\n";

echo "💡 NEXT STEPS:\n";
echo "1. Contact PalmPay support immediately\n";
echo "2. If urgent: Temporarily switch to director NIN\n";
echo "3. Monitor for PalmPay resolution\n";
echo "4. Switch back to BVN when issue is resolved\n";