<?php
/**
 * Create Missing Master Wallets for Active Companies
 * This script creates PalmPay master wallets for active companies that don't have one
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\PalmPay\VirtualAccountService;

echo "========================================\n";
echo "Create Missing Master Wallets\n";
echo "========================================\n\n";

// Get all active companies without master wallets
$companies = DB::table('companies')
    ->where('is_active', true)
    ->whereNull('palmpay_account_number')
    ->get();

$totalCompanies = count($companies);

if ($totalCompanies === 0) {
    echo "✅ All active companies already have master wallets.\n";
    exit(0);
}

echo "Found {$totalCompanies} active company(ies) without master wallets:\n\n";

$vaService = new VirtualAccountService();
$successCount = 0;
$failCount = 0;

foreach ($companies as $index => $company) {
    $num = $index + 1;
    echo "[{$num}/{$totalCompanies}] Company: {$company->name} (ID: {$company->id})\n";
    echo "  Email: {$company->email}\n";
    echo "  Phone: {$company->phone}\n";
    echo "  Director BVN: " . ($company->director_bvn ? 'Yes' : 'No') . "\n";
    echo "  Director NIN: " . ($company->director_nin ? 'Yes' : 'No') . "\n";
    echo "  RC Number: " . ($company->business_registration_number ? $company->business_registration_number : 'No') . "\n";
    
    // Check if company has any KYC data
    if (empty($company->director_bvn) && empty($company->director_nin) && empty($company->business_registration_number)) {
        echo "  ⚠️  Skipped: No KYC data (BVN, NIN, or RC number)\n\n";
        $failCount++;
        continue;
    }
    
    try {
        echo "  Creating master wallet...";
        
        $virtualAccount = $vaService->createVirtualAccount(
            $company->id,
            'company_master_' . $company->id,
            [
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
            ],
            '100033',
            null
        );
        
        // Update company
        DB::table('companies')->where('id', $company->id)->update([
            'palmpay_account_number' => $virtualAccount->account_number,
            'palmpay_account_name' => $virtualAccount->account_name,
            'palmpay_bank_name' => 'PalmPay',
            'palmpay_bank_code' => '100033',
        ]);
        
        echo " ✅ Success\n";
        echo "  Account Number: {$virtualAccount->account_number}\n";
        echo "  Account Name: {$virtualAccount->account_name}\n";
        $successCount++;
        
    } catch (\Exception $e) {
        echo " ❌ Failed\n";
        echo "  Error: {$e->getMessage()}\n";
        $failCount++;
    }
    
    echo "\n";
    
    // Small delay to avoid rate limiting
    usleep(500000); // 0.5 seconds
}

echo "========================================\n";
echo "Summary\n";
echo "========================================\n";
echo "Total companies: {$totalCompanies}\n";
echo "Successfully created: {$successCount}\n";
echo "Failed: {$failCount}\n";
echo "\n";

if ($successCount > 0) {
    echo "✅ Master wallets created successfully!\n";
}

if ($failCount > 0) {
    echo "⚠️  Some companies failed. Check logs for details.\n";
}

echo "\n";
