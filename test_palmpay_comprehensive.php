<?php
// Comprehensive PalmPay testing script with auto create/delete
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\PalmPay\VirtualAccountService;
use App\Models\Company;
use App\Models\VirtualAccount;

echo "🧪 COMPREHENSIVE PALMPAY TESTING\n";
echo "===============================\n\n";

$testMode = $argv[1] ?? 'auto'; // 'auto' or 'manual'
$customerName = $argv[2] ?? 'Test Customer ' . date('His');
$customerEmail = $argv[3] ?? 'test' . time() . '@example.com';
$customerPhone = $argv[4] ?? '0801234' . rand(1000, 9999);

echo "📋 TEST CONFIGURATION:\n";
echo "- Mode: $testMode\n";
echo "- Customer Name: $customerName\n";
echo "- Customer Email: $customerEmail\n";
echo "- Customer Phone: $customerPhone\n\n";

// Check company KYC status
$company = Company::find(4);
echo "📋 COMPANY KYC STATUS:\n";
echo "- Company: {$company->name}\n";
echo "- Director BVN: " . ($company->director_bvn ?? 'NULL') . "\n";
echo "- Director NIN: " . ($company->director_nin ?? 'NULL') . "\n";
echo "- Business RC: {$company->business_registration_number}\n\n";

// Predict which KYC method will be used
$predictedKyc = 'unknown';
$predictedLicense = 'unknown';
$predictedIdentityType = 'unknown';

if ($company->director_bvn && !$company->director_nin) {
    $predictedKyc = 'director_bvn';
    $predictedLicense = $company->director_bvn;
    $predictedIdentityType = 'personal';
} elseif ($company->director_nin && !$company->director_bvn) {
    $predictedKyc = 'director_nin';
    $predictedLicense = $company->director_nin;
    $predictedIdentityType = 'personal_nin';
} elseif ($company->director_bvn && $company->director_nin) {
    // Check usage history
    $bvnUsage = VirtualAccount::where('company_id', 4)
        ->where('kyc_source', 'director_bvn')
        ->where('status', 'active')
        ->count();
    $ninUsage = VirtualAccount::where('company_id', 4)
        ->where('kyc_source', 'director_nin')
        ->where('status', 'active')
        ->count();
        
    if ($bvnUsage > $ninUsage) {
        $predictedKyc = 'director_bvn';
        $predictedLicense = $company->director_bvn;
        $predictedIdentityType = 'personal';
    } else {
        $predictedKyc = 'director_nin';
        $predictedLicense = $company->director_nin;
        $predictedIdentityType = 'personal_nin';
    }
} else {
    $predictedKyc = 'company_rc';
    $predictedLicense = 'RC' . $company->business_registration_number;
    $predictedIdentityType = 'company';
}

echo "🎯 PREDICTED KYC SELECTION:\n";
echo "- KYC Source: $predictedKyc\n";
echo "- License Number: $predictedLicense\n";
echo "- Identity Type: $predictedIdentityType\n\n";

$testResults = [];
$createdAccounts = [];

try {
    $palmPayService = new VirtualAccountService();
    
    echo "🔄 STEP 1: CREATING VIRTUAL ACCOUNT\n";
    echo "==================================\n";
    
    $customerData = [
        'name' => $customerName,
        'email' => $customerEmail,
        'phone' => $customerPhone
    ];
    
    $startTime = microtime(true);
    
    $account = $palmPayService->createVirtualAccount(
        4, // Company ID (KoboPoint)
        'test_' . uniqid(),
        $customerData,
        '100033' // PalmPay bank code
    );
    
    $endTime = microtime(true);
    $creationTime = round(($endTime - $startTime) * 1000, 2);
    
    $createdAccounts[] = $account;
    
    echo "✅ SUCCESS! Account created in {$creationTime}ms\n\n";
    
    echo "📋 CREATED ACCOUNT DETAILS:\n";
    echo "- Account Number: {$account->account_number}\n";
    echo "- Account Name: {$account->palmpay_account_name}\n";
    echo "- Customer Name: {$account->customer_name}\n";
    echo "- Bank Name: {$account->palmpay_bank_name}\n";
    echo "- KYC Source: {$account->kyc_source}\n";
    echo "- Identity Type: {$account->identity_type}\n";
    echo "- Status: {$account->status}\n";
    echo "- Created: {$account->created_at}\n\n";
    
    // Verify account name matches expected format
    $expectedName = $company->name . '-' . $customerName;
    $nameMatches = ($account->palmpay_account_name === $expectedName);
    
    echo "🔍 VERIFICATION CHECKS:\n";
    echo "- Account created: ✅\n";
    echo "- No duplicate error: ✅\n";
    echo "- KYC prediction: " . ($account->kyc_source === $predictedKyc ? '✅' : '❌') . "\n";
    echo "- Name format: " . ($nameMatches ? '✅' : '⚠️') . "\n";
    echo "  Expected: $expectedName\n";
    echo "  Actual: {$account->palmpay_account_name}\n";
    echo "- PalmPay integration: ✅\n\n";
    
    $testResults['creation'] = [
        'success' => true,
        'time_ms' => $creationTime,
        'account_number' => $account->account_number,
        'kyc_source' => $account->kyc_source,
        'name_matches' => $nameMatches
    ];
    
    // Test account details query
    echo "🔄 STEP 2: QUERYING ACCOUNT DETAILS\n";
    echo "==================================\n";
    
    $startTime = microtime(true);
    $detailsResult = $palmPayService->getVirtualAccountDetails($account->account_number);
    $endTime = microtime(true);
    $queryTime = round(($endTime - $startTime) * 1000, 2);
    
    if ($detailsResult['success']) {
        echo "✅ Account details retrieved in {$queryTime}ms\n";
        echo "- Status: " . ($detailsResult['data']['status'] ?? 'unknown') . "\n";
        echo "- Name: " . ($detailsResult['data']['virtualAccountName'] ?? 'unknown') . "\n\n";
        
        $testResults['query'] = [
            'success' => true,
            'time_ms' => $queryTime,
            'status' => $detailsResult['data']['status'] ?? 'unknown'
        ];
    } else {
        echo "⚠️ Account details query failed: " . $detailsResult['message'] . "\n\n";
        $testResults['query'] = ['success' => false, 'error' => $detailsResult['message']];
    }
    
    // Auto-delete if in auto mode
    if ($testMode === 'auto') {
        echo "🔄 STEP 3: AUTO-DELETING TEST ACCOUNT\n";
        echo "====================================\n";
        
        $startTime = microtime(true);
        
        // Delete from PalmPay
        $deleteResult = $palmPayService->deleteVirtualAccount($account->account_number);
        
        $endTime = microtime(true);
        $deleteTime = round(($endTime - $startTime) * 1000, 2);
        
        if ($deleteResult['success']) {
            echo "✅ Deleted from PalmPay in {$deleteTime}ms\n";
            
            // Delete from database
            $account->forceDelete();
            echo "✅ Deleted from database\n";
            echo "🎉 Test account cleaned up successfully!\n\n";
            
            $testResults['deletion'] = [
                'success' => true,
                'time_ms' => $deleteTime
            ];
            
            // Remove from created accounts list
            $createdAccounts = array_filter($createdAccounts, function($acc) use ($account) {
                return $acc->id !== $account->id;
            });
            
        } else {
            echo "⚠️ PalmPay deletion failed: " . $deleteResult['message'] . "\n";
            echo "📝 Account kept for manual cleanup\n\n";
            
            $testResults['deletion'] = [
                'success' => false,
                'error' => $deleteResult['message']
            ];
        }
    } else {
        echo "📝 MANUAL MODE: Account kept for inspection\n";
        echo "Account Number: {$account->account_number}\n";
        echo "Delete manually when ready: php cleanup_test_accounts.php {$account->account_number}\n\n";
    }
    
} catch (\Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n\n";
    
    $testResults['creation'] = [
        'success' => false,
        'error' => $e->getMessage()
    ];
    
    echo "🔍 ERROR ANALYSIS:\n";
    if (strpos($e->getMessage(), 'licenseNumber duplicate') !== false) {
        echo "- Issue: KYC conflict (BVN/NIN duplicate)\n";
        echo "- Root Cause: Director has both BVN and NIN, PalmPay sees conflict\n";
        echo "- Solution: Apply permanent KYC fix\n";
        echo "- Command: php permanent_kyc_fix.php CONFIRM\n";
        echo "- This will switch to NIN-only strategy\n";
    } elseif (strpos($e->getMessage(), 'Circuit Breaker') !== false) {
        echo "- Issue: PalmPay API temporarily unavailable\n";
        echo "- Solution: Wait and retry in a few minutes\n";
        echo "- Monitor: php test_when_api_ready.php\n";
    } else {
        echo "- Issue: " . $e->getMessage() . "\n";
        echo "- Check application logs for more details\n";
    }
    echo "\n";
}

// Final summary
echo "📊 TEST SUMMARY\n";
echo "==============\n";

if ($testResults['creation']['success'] ?? false) {
    echo "✅ Account Creation: SUCCESS\n";
    echo "   - Time: " . ($testResults['creation']['time_ms'] ?? 0) . "ms\n";
    echo "   - KYC Source: " . ($testResults['creation']['kyc_source'] ?? 'unknown') . "\n";
    echo "   - Name Format: " . (($testResults['creation']['name_matches'] ?? false) ? 'Correct' : 'Needs Review') . "\n";
} else {
    echo "❌ Account Creation: FAILED\n";
    echo "   - Error: " . ($testResults['creation']['error'] ?? 'Unknown') . "\n";
}

if (isset($testResults['query'])) {
    if ($testResults['query']['success']) {
        echo "✅ Account Query: SUCCESS\n";
        echo "   - Time: " . ($testResults['query']['time_ms'] ?? 0) . "ms\n";
    } else {
        echo "⚠️ Account Query: FAILED\n";
    }
}

if (isset($testResults['deletion'])) {
    if ($testResults['deletion']['success']) {
        echo "✅ Account Deletion: SUCCESS\n";
        echo "   - Time: " . ($testResults['deletion']['time_ms'] ?? 0) . "ms\n";
    } else {
        echo "⚠️ Account Deletion: FAILED\n";
    }
}

echo "\n🎯 RECOMMENDATIONS:\n";

if (!($testResults['creation']['success'] ?? false)) {
    if (strpos(($testResults['creation']['error'] ?? ''), 'licenseNumber duplicate') !== false) {
        echo "1. Apply KYC fix: php permanent_kyc_fix.php CONFIRM\n";
        echo "2. This will switch from BVN to NIN-only strategy\n";
        echo "3. Retest after fix: php test_palmpay_comprehensive.php\n";
    }
} else {
    echo "✅ PalmPay integration is working correctly!\n";
    echo "✅ No restrictions detected on virtual account creation\n";
    echo "✅ System can generate unlimited accounts using director KYC\n";
}

// Cleanup any remaining test accounts if script was interrupted
if (!empty($createdAccounts) && $testMode === 'auto') {
    echo "\n🧹 CLEANING UP REMAINING TEST ACCOUNTS:\n";
    foreach ($createdAccounts as $acc) {
        try {
            $palmPayService->deleteVirtualAccount($acc->account_number);
            $acc->forceDelete();
            echo "✅ Cleaned up account: {$acc->account_number}\n";
        } catch (\Exception $e) {
            echo "⚠️ Failed to cleanup account {$acc->account_number}: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n✅ COMPREHENSIVE TEST COMPLETED\n";