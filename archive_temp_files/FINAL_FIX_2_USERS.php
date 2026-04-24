<?php

// Final fix for remaining 2 Oyitipay users with correct fresh KYC
// Run: php FINAL_FIX_2_USERS.php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ASSIGNING FRESH UNUSED KYC ===" . PHP_EOL;
$company = App\Models\Company::find(17);

// Get the 2 fresh KYC (IDs 2 and 3)
$kyc1 = App\Models\GlobalKycPool::find(2);
$kyc2 = App\Models\GlobalKycPool::find(3);

if (!$kyc1 || !$kyc2) {
    echo "❌ Could not find fresh KYC in pool!" . PHP_EOL;
    exit(1);
}

// Assign to backup slots
$company->backup_director_4_nin = $kyc1->kyc_number;
$company->backup_director_5_nin = $kyc2->kyc_number;
$company->save();

echo "✅ Assigned to backup_director_4: {$kyc1->kyc_number}" . PHP_EOL;
echo "✅ Assigned to backup_director_5: {$kyc2->kyc_number}" . PHP_EOL;
echo PHP_EOL;

// Now create VAs
echo "=== CREATING VIRTUAL ACCOUNTS ===" . PHP_EOL;
$service = new App\Services\PalmPay\VirtualAccountService();
$users = [615, 620];

$successCount = 0;
$failCount = 0;

foreach ($users as $userId) {
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
        echo "✅ User {$userId}: {$user->email} - {$va->account_number}" . PHP_EOL;
        $successCount++;
    } catch (Exception $e) {
        echo "❌ User {$userId}: {$e->getMessage()}" . PHP_EOL;
        $failCount++;
    }
    sleep(2);
    echo PHP_EOL;
}

echo "=== SUMMARY ===" . PHP_EOL;
echo "✅ Success: {$successCount}" . PHP_EOL;
echo "❌ Failed: {$failCount}" . PHP_EOL;
echo PHP_EOL;

echo "=== FINAL CHECK ===" . PHP_EOL;
$missing = App\Models\CompanyUser::where('company_id', 17)
    ->whereDoesntHave('virtualAccounts')
    ->count();
echo "Remaining users without VAs: {$missing}" . PHP_EOL;

if ($missing == 0) {
    echo PHP_EOL;
    echo "🎉 ALL OYITIPAY USERS NOW HAVE VIRTUAL ACCOUNTS!" . PHP_EOL;
} else {
    echo PHP_EOL;
    echo "⚠️  Still {$missing} users without VAs. Check logs for details." . PHP_EOL;
}
