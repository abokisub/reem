<?php
/**
 * Cleanup Test Virtual Accounts for a Company
 * This script deletes all virtual accounts for a specific company from both PalmPay and local database
 * Use this to clean up test accounts and start fresh
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\PalmPay\VirtualAccountService;

// Configuration
$companyId = 2; // PointWave Business (KoboPoint)
$dryRun = true; // Set to true to see what would be deleted without actually deleting

echo "========================================\n";
echo "Company Test Accounts Cleanup Script\n";
echo "========================================\n\n";

if ($dryRun) {
    echo "⚠️  DRY RUN MODE - No accounts will be deleted\n\n";
}

// Get company details
$company = DB::table('companies')->where('id', $companyId)->first();

if (!$company) {
    echo "❌ Company ID {$companyId} not found!\n";
    exit(1);
}

echo "Company: {$company->name} (ID: {$companyId})\n";
echo "Email: {$company->email}\n\n";

// Get all virtual accounts for this company
$virtualAccounts = DB::table('virtual_accounts')
    ->where('company_id', $companyId)
    ->get();

$totalAccounts = count($virtualAccounts);

if ($totalAccounts === 0) {
    echo "✅ No virtual accounts found for this company.\n";
    exit(0);
}

echo "Found {$totalAccounts} virtual account(s) to delete:\n\n";

$vaService = new VirtualAccountService();
$successCount = 0;
$failCount = 0;
$skippedCount = 0;

foreach ($virtualAccounts as $index => $va) {
    $num = $index + 1;
    echo "[{$num}/{$totalAccounts}] Account: {$va->account_number}\n";
    echo "  Customer: {$va->customer_name}\n";
    echo "  User ID: {$va->user_id}\n";
    echo "  Status: {$va->status}\n";
    echo "  Created: {$va->created_at}\n";
    
    if ($dryRun) {
        echo "  ⚠️  Would delete (DRY RUN)\n\n";
        $skippedCount++;
        continue;
    }
    
    // Delete from PalmPay first
    if (!empty($va->account_number)) {
        echo "  Deleting from PalmPay...";
        $result = $vaService->deleteVirtualAccount($va->account_number);
        
        if ($result['success']) {
            echo " ✅ Success\n";
        } else {
            echo " ⚠️  Failed: {$result['message']}\n";
            echo "  (Will still delete from local database)\n";
        }
    }
    
    // Delete from local database
    echo "  Deleting from database...";
    try {
        DB::table('virtual_accounts')->where('id', $va->id)->delete();
        echo " ✅ Success\n";
        $successCount++;
    } catch (\Exception $e) {
        echo " ❌ Failed: {$e->getMessage()}\n";
        $failCount++;
    }
    
    echo "\n";
    
    // Small delay to avoid rate limiting
    usleep(500000); // 0.5 seconds
}

echo "========================================\n";
echo "Cleanup Summary\n";
echo "========================================\n";

if ($dryRun) {
    echo "Mode: DRY RUN (no changes made)\n";
    echo "Accounts that would be deleted: {$skippedCount}\n";
} else {
    echo "Total accounts: {$totalAccounts}\n";
    echo "Successfully deleted: {$successCount}\n";
    echo "Failed: {$failCount}\n";
}

echo "\n";

if (!$dryRun && $successCount > 0) {
    echo "✅ Cleanup complete! The company can now create fresh virtual accounts.\n";
} elseif ($dryRun) {
    echo "ℹ️  To actually delete these accounts, set \$dryRun = false in the script.\n";
}

echo "\n";
