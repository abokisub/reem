<?php

// Final fix for Oyitipay - assign multiple backup KYC and create remaining VAs
// Run: php FIX_OYITIPAY_FINAL.php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FINAL FIX FOR OYITIPAY ===" . PHP_EOL;
echo PHP_EOL;

$company = App\Models\Company::find(17);
$globalKycService = new App\Services\GlobalKycService();

// Step 1: Assign 3 more backup KYC from pool
echo "Step 1: Assigning fresh backup KYC..." . PHP_EOL;

$backupSlots = [4, 5, 6]; // Use backup_director_4, 5, 6
$assignedCount = 0;

foreach ($backupSlots as $slot) {
    $ninField = "backup_director_{$slot}_nin";
    
    if (!empty($company->$ninField)) {
        echo "⚠️  Backup slot {$slot} already filled" . PHP_EOL;
        continue;
    }
    
    $kyc = $globalKycService->selectOptimalGlobalKyc('nin');
    if ($kyc) {
        $company->$ninField = $kyc->kyc_number;
        echo "✅ Assigned NIN {$kyc->kyc_number} to backup_director_{$slot}" . PHP_EOL;
        $assignedCount++;
    } else {
        echo "❌ No available KYC in pool" . PHP_EOL;
        break;
    }
}

$company->save();
echo "Assigned {$assignedCount} new backup KYC" . PHP_EOL;
echo PHP_EOL;

// Step 2: Create VAs for remaining users
echo "Step 2: Creating virtual accounts for remaining users..." . PHP_EOL;
$service = new App\Services\PalmPay\VirtualAccountService();

$remainingUsers = [615, 620];
$successCount = 0;
$failCount = 0;

foreach ($remainingUsers as $userId) {
    $user = App\Models\CompanyUser::find($userId);
    if (!$user) {
        echo "⚠️  User {$userId} not found" . PHP_EOL;
        continue;
    }
    
    echo "Processing User {$userId}: {$user->email}..." . PHP_EOL;
    
    try {
        $customerData = [
            'name' => trim(($user->first_name ?? 'User') . ' ' . ($user->last_name ?? '')),
            'email' => $user->email,
            'phone' => $user->phone,
        ];
        
        $va = $service->createVirtualAccount(17, $user->id, $customerData, '100033', $user->id);
        echo "✅ Created VA: {$va->account_number}" . PHP_EOL;
        $successCount++;
    } catch (Exception $e) {
        echo "❌ Failed: {$e->getMessage()}" . PHP_EOL;
        $failCount++;
    }
    
    sleep(2);
    echo PHP_EOL;
}

echo PHP_EOL;
echo "=== FINAL SUMMARY ===" . PHP_EOL;
echo "✅ Success: {$successCount}" . PHP_EOL;
echo "❌ Failed: {$failCount}" . PHP_EOL;
echo PHP_EOL;

// Step 3: Final verification
echo "Step 3: Final verification..." . PHP_EOL;
$allUsers = App\Models\CompanyUser::where('company_id', 17)->get();
$missingCount = 0;

foreach ($allUsers as $user) {
    $hasVA = App\Models\VirtualAccount::where('company_user_id', $user->id)->exists();
    if (!$hasVA) {
        echo "❌ User {$user->id}: {$user->email} - NO VA" . PHP_EOL;
        $missingCount++;
    }
}

if ($missingCount === 0) {
    echo "🎉 ALL USERS HAVE VIRTUAL ACCOUNTS!" . PHP_EOL;
} else {
    echo "⚠️  {$missingCount} users still missing virtual accounts" . PHP_EOL;
}

echo PHP_EOL;
echo "=== DONE ===" . PHP_EOL;
