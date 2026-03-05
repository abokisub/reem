<?php
// Comprehensive test to verify PalmPay restrictions and BVN usage
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Services\PalmPay\VirtualAccountService;

$confirm = $argv[1] ?? null;

if ($confirm !== 'CONFIRM') {
    echo "🧪 COMPREHENSIVE PALMPAY RESTRICTIONS TEST\n";
    echo "=========================================\n\n";
    echo "This script will:\n";
    echo "✅ 1. Test current director BVN (22490148602)\n";
    echo "✅ 2. Test director NIN as alternative (35257106066)\n";
    echo "✅ 3. Create multiple virtual accounts rapidly\n";
    echo "✅ 4. Delete all test accounts immediately\n";
    echo "✅ 5. Verify no restrictions exist\n\n";
    echo "This will prove whether PalmPay has restrictions or not.\n\n";
    echo "⚠️  WARNING: Creates and deletes test accounts on PalmPay\n";
    echo "To proceed, run: php test_palmpay_restrictions.php CONFIRM\n";
    exit(1);
}

echo "🧪 COMPREHENSIVE PALMPAY RESTRICTIONS TEST\n";
echo "=========================================\n\n";

$palmPayService = new VirtualAccountService();
$testAccounts = [];
$testResults = [];

try {
    // Get Company 4
    $company = Company::find(4);
    if (!$company) {
        throw new \Exception("Company 4 not found");
    }
    
    echo "📋 COMPANY DATA:\n";
    echo "- Name: {$company->name}\n";
    echo "- Director BVN: {$company->director_bvn}\n";
    echo "- Director NIN: {$company->director_nin}\n";
    echo "- Business RC: {$company->business_registration_number}\n\n";
    
    // TEST 1: Current Director BVN
    echo "🧪 TEST 1: DIRECTOR BVN (22490148602)\n";
    echo "=====================================\n";
    
    for ($i = 1; $i <= 3; $i++) {
        echo "Creating test account $i with director BVN...\n";
        
        try {
            $testCustomer = [
                'name' => "BVN Test Customer $i",
                'email' => "bvn_test_$i@example.com",
                'phone' => "0801234567$i"
            ];
            
            $account = $palmPayService->createVirtualAccount(
                4,
                'bvn_test_' . $i . '_' . uniqid(),
                $testCustomer,
                '100033'
            );
            
            $testAccounts[] = $account;
            
            echo "✅ SUCCESS: Account {$account->account_number} created\n";
            echo "   Name: {$account->customer_name}\n";
            echo "   KYC: {$account->kyc_source}\n\n";
            
            $testResults['bvn_test_' . $i] = [
                'status' => 'SUCCESS',
                'account' => $account->account_number,
                'kyc_source' => $account->kyc_source
            ];
            
            // Small delay between requests
            sleep(1);
            
        } catch (\Exception $e) {
            echo "❌ FAILED: " . $e->getMessage() . "\n\n";
            $testResults['bvn_test_' . $i] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // TEST 2: Director NIN (temporarily switch)
    echo "🧪 TEST 2: DIRECTOR NIN (35257106066)\n";
    echo "=====================================\n";
    
    $originalBvn = $company->director_bvn;
    
    echo "Temporarily switching to director NIN...\n";
    $company->update(['director_bvn' => null]);
    
    for ($i = 1; $i <= 3; $i++) {
        echo "Creating test account $i with director NIN...\n";
        
        try {
            $testCustomer = [
                'name' => "NIN Test Customer $i",
                'email' => "nin_test_$i@example.com",
                'phone' => "0802345678$i"
            ];
            
            $account = $palmPayService->createVirtualAccount(
                4,
                'nin_test_' . $i . '_' . uniqid(),
                $testCustomer,
                '100033'
            );
            
            $testAccounts[] = $account;
            
            echo "✅ SUCCESS: Account {$account->account_number} created\n";
            echo "   Name: {$account->customer_name}\n";
            echo "   KYC: {$account->kyc_source}\n\n";
            
            $testResults['nin_test_' . $i] = [
                'status' => 'SUCCESS',
                'account' => $account->account_number,
                'kyc_source' => $account->kyc_source
            ];
            
            // Small delay between requests
            sleep(1);
            
        } catch (\Exception $e) {
            echo "❌ FAILED: " . $e->getMessage() . "\n\n";
            $testResults['nin_test_' . $i] = [
                'status' => 'FAILED',
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Restore original BVN
    echo "Restoring original director BVN...\n";
    $company->update(['director_bvn' => $originalBvn]);
    echo "✅ Director BVN restored\n\n";
    
    // TEST 3: Business RC Number
    echo "🧪 TEST 3: BUSINESS RC NUMBER (RC-9058987)\n";
    echo "==========================================\n";
    
    // Temporarily clear both BVN and NIN to force RC usage
    $originalNin = $company->director_nin;
    $company->update([
        'director_bvn' => null,
        'director_nin' => null
    ]);
    
    try {
        echo "Creating test account with business RC number...\n";
        
        $testCustomer = [
            'name' => "RC Test Customer",
            'email' => "rc_test@example.com",
            'phone' => "08034567890"
        ];
        
        $account = $palmPayService->createVirtualAccount(
            4,
            'rc_test_' . uniqid(),
            $testCustomer,
            '100033'
        );
        
        $testAccounts[] = $account;
        
        echo "✅ SUCCESS: Account {$account->account_number} created\n";
        echo "   Name: {$account->customer_name}\n";
        echo "   KYC: {$account->kyc_source}\n\n";
        
        $testResults['rc_test'] = [
            'status' => 'SUCCESS',
            'account' => $account->account_number,
            'kyc_source' => $account->kyc_source
        ];
        
    } catch (\Exception $e) {
        echo "❌ FAILED: " . $e->getMessage() . "\n\n";
        $testResults['rc_test'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // Restore original values
    echo "Restoring original company data...\n";
    $company->update([
        'director_bvn' => $originalBvn,
        'director_nin' => $originalNin
    ]);
    echo "✅ Company data restored\n\n";
    
} catch (\Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n\n";
} finally {
    // CLEANUP: Delete all test accounts
    echo "🧹 CLEANUP: DELETING ALL TEST ACCOUNTS\n";
    echo "======================================\n";
    
    foreach ($testAccounts as $account) {
        try {
            echo "Deleting account {$account->account_number}...\n";
            
            // Delete on PalmPay side
            $deleteResult = $palmPayService->deleteVirtualAccount($account->account_number);
            
            if ($deleteResult['success']) {
                echo "✅ Deleted on PalmPay: {$account->account_number}\n";
            } else {
                echo "⚠️  PalmPay deletion failed: " . $deleteResult['message'] . "\n";
            }
            
            // Delete from our database
            $account->forceDelete();
            echo "✅ Deleted from database: {$account->account_number}\n\n";
            
        } catch (\Exception $e) {
            echo "❌ Cleanup failed for {$account->account_number}: " . $e->getMessage() . "\n\n";
        }
    }
}

// RESULTS SUMMARY
echo "📊 TEST RESULTS SUMMARY\n";
echo "=======================\n\n";

$successCount = 0;
$failureCount = 0;

foreach ($testResults as $testName => $result) {
    if ($result['status'] === 'SUCCESS') {
        echo "✅ $testName: SUCCESS (KYC: {$result['kyc_source']})\n";
        $successCount++;
    } else {
        echo "❌ $testName: FAILED - {$result['error']}\n";
        $failureCount++;
    }
}

echo "\n📈 STATISTICS:\n";
echo "- Successful tests: $successCount\n";
echo "- Failed tests: $failureCount\n";
echo "- Total tests: " . ($successCount + $failureCount) . "\n\n";

echo "🎯 CONCLUSIONS:\n";
if ($successCount > 0) {
    echo "✅ PalmPay IS working - some KYC methods successful\n";
    echo "✅ No blanket restrictions on your account\n";
    if ($failureCount > 0) {
        echo "⚠️  Specific KYC methods have issues (likely BVN)\n";
        echo "💡 Use working KYC method as temporary solution\n";
    }
} else {
    echo "❌ ALL tests failed - broader PalmPay issue\n";
    echo "🚨 Contact PalmPay support immediately\n";
}

echo "\n💡 RECOMMENDATIONS:\n";
echo "1. Use the KYC method that worked for production\n";
echo "2. Contact PalmPay about failed KYC methods\n";
echo "3. Monitor which specific errors are occurring\n";
echo "4. Request PalmPay to investigate BVN: 22490148602\n\n";

echo "✅ COMPREHENSIVE TEST COMPLETED\n";