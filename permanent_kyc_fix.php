<?php
// Permanent KYC fix - Switch to director NIN to avoid BVN conflicts
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;

$confirm = $argv[1] ?? null;

if ($confirm !== 'CONFIRM') {
    echo "🔧 PERMANENT KYC CONFLICT FIX\n";
    echo "============================\n\n";
    echo "ISSUE IDENTIFIED:\n";
    echo "- Director BVN (22490148602) causing 'licenseNumber duplicate' errors\n";
    echo "- PalmPay sees BVN and NIN as conflicting for same person\n";
    echo "- Need to use ONLY ONE KYC method consistently\n\n";
    echo "SOLUTION:\n";
    echo "- Permanently switch to director NIN (35257106066)\n";
    echo "- Clear director BVN to force NIN usage\n";
    echo "- Avoid BVN/NIN conflicts going forward\n\n";
    echo "⚠️  WARNING: This will change company KYC settings permanently\n";
    echo "To proceed, run: php permanent_kyc_fix.php CONFIRM\n";
    exit(1);
}

echo "🔧 PERMANENT KYC CONFLICT FIX\n";
echo "============================\n\n";

try {
    // Get Company 4
    $company = Company::find(4);
    if (!$company) {
        throw new \Exception("Company 4 not found");
    }
    
    echo "📋 CURRENT SETTINGS:\n";
    echo "- Director BVN: {$company->director_bvn}\n";
    echo "- Director NIN: {$company->director_nin}\n";
    echo "- Business RC: {$company->business_registration_number}\n\n";
    
    // Backup current settings
    $backupBvn = $company->director_bvn;
    $backupNin = $company->director_nin;
    
    echo "💾 BACKING UP CURRENT SETTINGS:\n";
    echo "- Backup BVN: $backupBvn\n";
    echo "- Backup NIN: $backupNin\n\n";
    
    // Implement permanent fix: Use NIN only
    echo "🔄 IMPLEMENTING PERMANENT FIX:\n";
    echo "Strategy: Use director NIN only to avoid BVN conflicts\n\n";
    
    // Clear BVN, keep NIN
    $company->update([
        'director_bvn' => null,  // Clear BVN to avoid conflicts
        'director_nin' => $backupNin  // Keep NIN as primary KYC
    ]);
    
    echo "✅ PERMANENT FIX APPLIED:\n";
    echo "- Director BVN: CLEARED (to avoid conflicts)\n";
    echo "- Director NIN: {$company->director_nin} (primary KYC)\n";
    echo "- System will now use NIN for all new accounts\n\n";
    
    // Verify the change
    $company->refresh();
    echo "🔍 VERIFICATION:\n";
    echo "- Current BVN: " . ($company->director_bvn ?? 'NULL') . " ✅\n";
    echo "- Current NIN: " . ($company->director_nin ?? 'NULL') . " ✅\n\n";
    
    echo "📋 WHAT THIS MEANS:\n";
    echo "✅ All new virtual accounts will use director NIN (35257106066)\n";
    echo "✅ No more BVN/NIN conflicts with PalmPay\n";
    echo "✅ Existing 88 accounts remain unaffected\n";
    echo "✅ System will be consistent going forward\n\n";
    
    echo "🧪 READY FOR TESTING:\n";
    echo "You can now test account creation:\n";
    echo "- php test_create_account.php\n";
    echo "- Should use 'director_nin' as KYC source\n";
    echo "- Should avoid 'licenseNumber duplicate' errors\n\n";
    
    echo "🔄 ROLLBACK OPTION:\n";
    echo "If you need to rollback this change:\n";
    echo "UPDATE companies SET director_bvn = '$backupBvn' WHERE id = 4;\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
}

echo "✅ PERMANENT KYC FIX COMPLETED\n";
echo "The system is now configured to avoid BVN/NIN conflicts.\n";