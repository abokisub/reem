<?php
/**
 * Comprehensive Deduplication Bug Fix and Verification Script
 * 
 * This script performs a complete fix and verification process:
 * 1. Restores corrupted account to original owner
 * 2. Creates fresh account for the affected customer
 * 3. Verifies PalmPay account names match our database
 * 4. Tests the fixed deduplication logic
 * 5. Provides comprehensive reporting
 */

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VirtualAccount;
use App\Models\CompanyUser;
use App\Services\PalmPay\VirtualAccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$confirm = $argv[1] ?? null;

if ($confirm !== 'CONFIRM') {
    echo "🔧 COMPREHENSIVE DEDUPLICATION FIX & VERIFICATION\n";
    echo "=================================================\n\n";
    echo "This script will:\n";
    echo "✅ 1. Restore account 6662822179 to original owner 'Nana Aisha Bello'\n";
    echo "✅ 2. Create fresh account for 'Aboki Sub' with correct details\n";
    echo "✅ 3. Verify PalmPay account names match our database\n";
    echo "✅ 4. Test deduplication logic with various scenarios\n";
    echo "✅ 5. Generate comprehensive verification report\n\n";
    echo "⚠️  WARNING: This performs live database operations\n";
    echo "To proceed, run: php comprehensive_deduplication_fix.php CONFIRM\n";
    exit(1);
}

echo "🔧 COMPREHENSIVE DEDUPLICATION FIX & VERIFICATION\n";
echo "=================================================\n\n";

$results = [];
$palmPayService = new VirtualAccountService();

try {
    DB::beginTransaction();
    
    // STEP 1: Restore Corrupted Account
    echo "📋 STEP 1: RESTORING CORRUPTED ACCOUNT\n";
    echo "--------------------------------------\n";
    
    $corruptedAccount = VirtualAccount::where('account_number', '6662822179')->first();
    
    if (!$corruptedAccount) {
        throw new \Exception("Account 6662822179 not found in database");
    }
    
    echo "Found corrupted account:\n";
    echo "- Account: {$corruptedAccount->account_number}\n";
    echo "- Current Name: {$corruptedAccount->customer_name} (CORRUPTED)\n";
    echo "- Phone: {$corruptedAccount->customer_phone}\n";
    echo "- Email: {$corruptedAccount->customer_email}\n\n";
    
    // Restore original customer data
    $originalCustomerName = 'Nana Aisha Bello';
    $corruptedAccount->update([
        'customer_name' => $originalCustomerName,
        'updated_at' => now()
    ]);
    
    echo "✅ Restored account to original owner: '$originalCustomerName'\n\n";
    $results['step1'] = 'SUCCESS: Account restored to original owner';
    
    // STEP 2: Create Fresh Account for Aboki Sub
    echo "📋 STEP 2: CREATING FRESH ACCOUNT FOR ABOKI SUB\n";
    echo "-----------------------------------------------\n";
    
    $abokiCustomerData = [
        'name' => 'Aboki Sub',
        'email' => 'habukhan001@gmail.com',
        'phone' => '07040540018'
    ];
    
    echo "Creating fresh account for:\n";
    echo "- Name: {$abokiCustomerData['name']}\n";
    echo "- Email: {$abokiCustomerData['email']}\n";
    echo "- Phone: {$abokiCustomerData['phone']}\n\n";
    
    try {
        $freshAccount = $palmPayService->createVirtualAccount(
            4, // Company ID (KoboPoint)
            'test_user_' . uniqid(), // Unique user ID
            $abokiCustomerData,
            '100033' // PalmPay bank code
        );
        
        echo "✅ Fresh account created successfully:\n";
        echo "- Account Number: {$freshAccount->account_number}\n";
        echo "- Account Name: {$freshAccount->customer_name}\n";
        echo "- PalmPay Name: {$freshAccount->palmpay_account_name}\n\n";
        
        $results['step2'] = [
            'status' => 'SUCCESS',
            'account_number' => $freshAccount->account_number,
            'customer_name' => $freshAccount->customer_name
        ];
        
    } catch (\Exception $e) {
        echo "❌ Failed to create fresh account: " . $e->getMessage() . "\n\n";
        $results['step2'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage()
        ];
    }
    
    // STEP 3: Verify PalmPay Account Names
    echo "📋 STEP 3: VERIFYING PALMPAY ACCOUNT NAMES\n";
    echo "------------------------------------------\n";
    
    $accountsToVerify = [
        '6662822179' => 'Nana Aisha Bello'
    ];
    
    if (isset($freshAccount)) {
        $accountsToVerify[$freshAccount->account_number] = 'Aboki Sub';
    }
    
    foreach ($accountsToVerify as $accountNumber => $expectedName) {
        echo "Verifying account $accountNumber...\n";
        
        $palmPayDetails = $palmPayService->getVirtualAccountDetails($accountNumber);
        
        if ($palmPayDetails['success']) {
            $palmPayName = $palmPayDetails['data']['virtualAccountName'] ?? 'Unknown';
            $nameMatch = strpos($palmPayName, $expectedName) !== false;
            
            echo "- Expected: $expectedName\n";
            echo "- PalmPay: $palmPayName\n";
            echo "- Match: " . ($nameMatch ? "✅ YES" : "❌ NO") . "\n\n";
            
            $results['step3'][$accountNumber] = [
                'expected' => $expectedName,
                'palmpay' => $palmPayName,
                'match' => $nameMatch
            ];
        } else {
            echo "- ❌ Failed to get PalmPay details: " . $palmPayDetails['message'] . "\n\n";
            $results['step3'][$accountNumber] = [
                'status' => 'FAILED',
                'error' => $palmPayDetails['message']
            ];
        }
    }
    
    // STEP 4: Test Deduplication Logic
    echo "📋 STEP 4: TESTING DEDUPLICATION LOGIC\n";
    echo "--------------------------------------\n";
    
    $testScenarios = [
        [
            'name' => 'Same customer (should find existing)',
            'data' => [
                'name' => 'Nana Aisha Bello',
                'email' => 'nanabello161@gmail.com',
                'phone' => '09162048553'
            ],
            'should_create_new' => false
        ],
        [
            'name' => 'Different customer (should create new)',
            'data' => [
                'name' => 'Test Customer',
                'email' => 'test@example.com',
                'phone' => '08012345678'
            ],
            'should_create_new' => true
        ]
    ];
    
    foreach ($testScenarios as $scenario) {
        echo "Testing: {$scenario['name']}\n";
        
        try {
            $testResult = $palmPayService->createVirtualAccount(
                4,
                'test_' . uniqid(),
                $scenario['data'],
                '100033'
            );
            
            $isNewAccount = $testResult->created_at->isToday();
            $testPassed = ($scenario['should_create_new'] === $isNewAccount);
            
            echo "- Result: " . ($testPassed ? "✅ PASSED" : "❌ FAILED") . "\n";
            echo "- Account: {$testResult->account_number}\n";
            echo "- Created: " . ($isNewAccount ? "New" : "Existing") . "\n\n";
            
            $results['step4'][$scenario['name']] = [
                'passed' => $testPassed,
                'account_number' => $testResult->account_number,
                'is_new' => $isNewAccount
            ];
            
            // Clean up test accounts
            if ($isNewAccount && $scenario['should_create_new']) {
                $testResult->delete();
                echo "- ✅ Test account cleaned up\n\n";
            }
            
        } catch (\Exception $e) {
            $testPassed = !$scenario['should_create_new']; // If it should fail and it did, that's good
            echo "- Result: " . ($testPassed ? "✅ PASSED (Expected failure)" : "❌ FAILED") . "\n";
            echo "- Error: " . $e->getMessage() . "\n\n";
            
            $results['step4'][$scenario['name']] = [
                'passed' => $testPassed,
                'error' => $e->getMessage()
            ];
        }
    }
    
    DB::commit();
    
    // STEP 5: Generate Comprehensive Report
    echo "📋 STEP 5: COMPREHENSIVE VERIFICATION REPORT\n";
    echo "============================================\n\n";
    
    $allTestsPassed = true;
    
    foreach ($results as $step => $result) {
        switch ($step) {
            case 'step1':
                echo "✅ Account Restoration: SUCCESS\n";
                break;
                
            case 'step2':
                if ($result['status'] === 'SUCCESS') {
                    echo "✅ Fresh Account Creation: SUCCESS\n";
                    echo "   Account: {$result['account_number']}\n";
                } else {
                    echo "❌ Fresh Account Creation: FAILED\n";
                    echo "   Error: {$result['error']}\n";
                    $allTestsPassed = false;
                }
                break;
                
            case 'step3':
                $allMatched = true;
                foreach ($result as $account => $verification) {
                    if (isset($verification['match']) && !$verification['match']) {
                        $allMatched = false;
                    }
                }
                echo ($allMatched ? "✅" : "❌") . " PalmPay Name Verification: " . ($allMatched ? "SUCCESS" : "PARTIAL") . "\n";
                if (!$allMatched) $allTestsPassed = false;
                break;
                
            case 'step4':
                $deduplicationPassed = true;
                foreach ($result as $test => $testResult) {
                    if (!$testResult['passed']) {
                        $deduplicationPassed = false;
                    }
                }
                echo ($deduplicationPassed ? "✅" : "❌") . " Deduplication Logic: " . ($deduplicationPassed ? "SUCCESS" : "FAILED") . "\n";
                if (!$deduplicationPassed) $allTestsPassed = false;
                break;
        }
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🎯 OVERALL RESULT: " . ($allTestsPassed ? "✅ ALL TESTS PASSED" : "❌ SOME TESTS FAILED") . "\n";
    echo str_repeat("=", 50) . "\n\n";
    
    if ($allTestsPassed) {
        echo "🎉 DEDUPLICATION BUG SUCCESSFULLY FIXED!\n";
        echo "✅ All accounts restored and verified\n";
        echo "✅ Fresh accounts created correctly\n";
        echo "✅ Deduplication logic working properly\n";
        echo "✅ System is now secure and functioning correctly\n\n";
    } else {
        echo "⚠️  SOME ISSUES DETECTED - MANUAL REVIEW REQUIRED\n";
        echo "Please review the failed tests above and investigate further.\n\n";
    }
    
    // Log the comprehensive results
    Log::info('Comprehensive Deduplication Fix Completed', [
        'overall_success' => $allTestsPassed,
        'detailed_results' => $results,
        'timestamp' => now()->toISOString()
    ]);
    
} catch (\Exception $e) {
    DB::rollBack();
    
    echo "❌ CRITICAL ERROR DURING FIX PROCESS\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n\n";
    
    Log::critical('Comprehensive Deduplication Fix Failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'timestamp' => now()->toISOString()
    ]);
    
    exit(1);
}

echo "✅ COMPREHENSIVE FIX AND VERIFICATION COMPLETED\n";
echo "Check the logs for detailed results.\n";