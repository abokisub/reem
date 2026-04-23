<?php

// Fix remaining 2 users for Oyitipay
// Run: php FIX_REMAINING_2_USERS.php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ASSIGNING FRESH KYC ===" . PHP_EOL;
$company = App\Models\Company::find(17);
$kycService = new App\Services\GlobalKycService();

// Assign 2 fresh KYC
$kyc1 = $kycService->selectOptimalGlobalKyc();
$company->backup_director_4_nin = $kyc1->kyc_number;
$company->save();
echo "✅ Assigned KYC to backup_director_4: {$kyc1->kyc_number}" . PHP_EOL;

$kyc2 = $kycService->selectOptimalGlobalKyc();
$company->backup_director_5_nin = $kyc2->kyc_number;
$company->save();
echo "✅ Assigned KYC to backup_director_5: {$kyc2->kyc_number}" . PHP_EOL;
echo PHP_EOL;

// Now create VAs for the 2 remaining users
echo "=== CREATING VIRTUAL ACCOUNTS ===" . PHP_EOL;
$service = new App\Services\PalmPay\VirtualAccountService();
$users = [615, 620];

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
    } catch (Exception $e) {
        echo "❌ User {$userId}: {$e->getMessage()}" . PHP_EOL;
    }
    sleep(2);
    echo PHP_EOL;
}

echo "=== DONE ===" . PHP_EOL;
echo PHP_EOL;

// Final verification
$missingCount = App\Models\CompanyUser::where('company_id', 17)
    ->whereDoesntHave('virtualAccounts')
    ->count();
echo "Remaining users without VAs: {$missingCount}" . PHP_EOL;
