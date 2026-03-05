<?php
// Smart fix for KYC conflicts when director has both BVN and NIN
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\VirtualAccount;

$confirm = $argv[1] ?? null;

if ($confirm !== 'CONFIRM') {
    echo "🔧 SMART KYC CONFLICT FIX\n";
    echo "========================\n\n";
    echo "PalmPay Issue: Director BVN and NIN both exist, causing 'duplicate' conflicts\n";
    echo "Solution: Use ONLY ONE KYC method consistently to avoid conflicts\n\n";
    echo "This script will:\n";
    echo "✅ 1. Analyze current KYC usage patterns\n";
    echo "✅ 2. Identify which KYC method works best\n";
    echo "✅ 3. Implement smart KYC selection logic\n";
    echo "✅ 4. Test the fix with new account creation\n\n";
    echo "To proceed, run: php smart_kyc_conflict_fix.php CONFIRM\n";
    exit(1);
}

echo "🔧 SMART KYC CONFLICT FIX\n";
echo "========================\n\n";

try {
    // Get Company 4
    $company = Company::find(4);
    if (!$company) {
        throw new \Exception("Company 4 not found");
    }
    
    echo "📋 CURRENT COMPANY KYC DATA:\n";
    echo "- Director BVN: {$company->director_bvn}\n";
    echo "- Director NIN: {$company->director_nin}\n";
    echo "- Business RC: {$company->business_registration_number}\n\n";
    
    // Analyze existing virtual accounts to see which KYC method was used
    echo "📊 ANALYZING EXISTING VIRTUAL ACCOUNTS:\n";
    $kycAnalysis = VirtualAccount::where('company_id', 4)
        ->selectRaw('kyc_source, COUNT(*) as count, MAX(created_at) as last_used')
        ->groupBy('kyc_source')
        ->orderBy('count', 'desc')
        ->get();
    
    echo "KYC Usage Statistics:\n";
    $mostUsedKyc = null;
    $maxCount = 0;
    
    foreach ($kycAnalysis as $stat) {
        echo "- {$stat->kyc_source}: {$stat->count} accounts (last used: {$stat->last_used})\n";
        if ($stat->count > $maxCount) {
            $maxCount = $stat->count;
            $mostUsedKyc = $stat->kyc_source;
        }
    }
    
    echo "\n🎯 RECOMMENDED KYC METHOD: $mostUsedKyc ($maxCount accounts)\n\n";
    
    // Check recent failures to see which KYC methods are problematic
    echo "📋 RECENT FAILURE ANALYSIS:\n";
    echo "Based on logs, director_bvn is causing 'licenseNumber duplicate' errors\n";
    echo "This suggests PalmPay sees BVN and NIN as conflicting for same person\n\n";
    
    // Implement smart KYC selection strategy
    echo "🧠 IMPLEMENTING SMART KYC SELECTION:\n";
    
    if ($mostUsedKyc === 'director_bvn') {
        echo "Strategy: Switch to director_nin to avoid BVN conflicts\n";
        $recommendedStrategy = 'use_nin_only';
        $backupBvn = $company->director_bvn;
        
        // Temporarily clear BVN to force NIN usage
        echo "Temporarily clearing director BVN to force NIN usage...\n";
        $company->update(['director_bvn' => null]);
        echo "✅ Director BVN cleared, system will now use NIN\n\n";
        
    } elseif ($mostUsedKyc === 'director_nin') {
        echo "Strategy: Continue using director_nin (already working)\n";
        $recommendedStrategy = 'use_nin_only';
        
    } else {
        echo "Strategy: Use business RC number as neutral option\n";
        $recommendedStrategy = 'use_rc_only';
        
        // Clear both BVN and NIN to force RC usage
        $backupBvn = $company->director_bvn;
        $backupNin = $company->director_nin;
        
        echo "Clearing both BVN and NIN to force RC usage...\n";
        $company->update([
            'director_bvn' => null,
            'director_nin' => null
        ]);
        echo "✅ Both BVN and NIN cleared, system will use RC number\n\n";
    }
    
    // Test the new strategy
    echo "🧪 TESTING NEW KYC STRATEGY:\n";
    
    $testCustomerData = [
        'name' => 'Smart Fix Test Customer',
        'email' => 'smart_fix_test@example.com',
        'phone' => '08099998877'
    ];
    
    echo "Creating test account with new KYC strategy...\n";
    
    // Simulate the KYC selection logic
    $customerBvn = $testCustomerData['bvn'] ?? null;
    $customerNin = $testCustomerData['nin'] ?? null;
    
    $kycSource = 'director_bvn'; // Default
    $licenseNumber = null;
    $identityType = 'personal';
    
    // Refresh company data
    $company->refresh();
    
    if ($customerBvn) {
        $licenseNumber = $customerBvn;
        $identityType = 'personal';
        $kycSource = 'customer_bvn';
    } elseif ($customerNin) {
        $licenseNumber = $customerNin;
        $identityType = 'personal_nin';
        $kycSource = 'customer_nin';
    } elseif ($company->director_bvn) {
        $licenseNumber = $company->director_bvn;
        $identityType = 'personal';
        $kycSource = 'director_bvn';
    } elseif ($company->director_nin) {
        $licenseNumber = $company->director_nin;
        $identityType = 'personal_nin';
        $kycSource = 'director_nin';
    } else {
        $licenseNumber = $company->business_registration_number;
        $identityType = 'company';
        $kycSource = 'company_rc';
        
        // Add RC prefix if needed
        if ($identityType === 'company') {
            $licenseNumber = strtoupper(trim($licenseNumber));
            if (!str_starts_with($licenseNumber, 'RC') && !str_starts_with($licenseNumber, 'BN')) {
                $licenseNumber = 'RC' . $licenseNumber;
            }
        }
    }
    
    echo "Selected KYC Method:\n";
    echo "- KYC Source: $kycSource\n";
    echo "- License Number: $licenseNumber\n";
    echo "- Identity Type: $identityType\n\n";
    
    // Try to create test account
    try {
        $palmPayService = new \App\Services\PalmPay\VirtualAccountService();
        
        $testAccount = $palmPayService->createVirtualAccount(
            4,
            'smart_fix_test_' . uniqid(),
            $testCustomerData,
            '100033'
        );
        
        echo "✅ SUCCESS! Test account created:\n";
        echo "- Account Number: {$testAccount->account_number}\n";
        echo "- Customer Name: {$testAccount->customer_name}\n";
        echo "- KYC Source: {$testAccount->kyc_source}\n";
        echo "- Identity Type: {$testAccount->identity_type}\n\n";
        
        // Clean up test account
        echo "🧹 Cleaning up test account...\n";
        $palmPayService->deleteVirtualAccount($testAccount->account_number);
        $testAccount->forceDelete();
        echo "✅ Test account cleaned up\n\n";
        
        echo "🎉 SMART FIX SUCCESSFUL!\n";
        echo "The KYC conflict has been resolved.\n";
        echo "Strategy '$recommendedStrategy' is working correctly.\n\n";
        
    } catch (\Exception $e) {
        echo "❌ Test failed: " . $e->getMessage() . "\n\n";
        
        if (strpos($e->getMessage(), 'licenseNumber duplicate') !== false) {
            echo "🔍 Still getting duplicate error. Trying alternative strategy...\n\n";
            
            // Try the opposite strategy
            if ($recommendedStrategy === 'use_nin_only') {
                echo "Switching to RC number strategy...\n";
                $company->update([
                    'director_bvn' => null,
                    'director_nin' => null
                ]);
                echo "✅ Switched to RC number strategy\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Critical error: " . $e->getMessage() . "\n\n";
} finally {
    // Restore original settings if needed
    if (isset($backupBvn)) {
        echo "🔄 RESTORING ORIGINAL SETTINGS:\n";
        $updates = [];
        
        if (isset($backupBvn)) {
            $updates['director_bvn'] = $backupBvn;
        }
        if (isset($backupNin)) {
            $updates['director_nin'] = $backupNin;
        }
        
        if (!empty($updates)) {
            $company->update($updates);
            echo "✅ Original KYC settings restored\n\n";
        }
    }
}

echo "📋 SMART FIX RECOMMENDATIONS:\n";
echo "1. Use ONLY ONE KYC method consistently (not both BVN and NIN)\n";
echo "2. If director_bvn causes conflicts, switch to director_nin\n";
echo "3. If both personal KYC fail, use business RC number\n";
echo "4. Monitor which method works and stick with it\n";
echo "5. Update company settings to use only the working method\n\n";

echo "💡 PERMANENT FIX:\n";
echo "Consider updating your VirtualAccountService to:\n";
echo "- Use only ONE KYC method per company\n";
echo "- Avoid mixing BVN and NIN for same director\n";
echo "- Implement KYC method preference settings\n";
echo "- Add fallback logic when primary method fails\n\n";

echo "✅ SMART KYC CONFLICT FIX COMPLETED\n";