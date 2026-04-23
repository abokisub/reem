<?php

// Manual fix script for Oyitipay missing virtual accounts
// Run: php FIX_OYITIPAY_MANUAL.php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FIXING OYITIPAY MISSING VIRTUAL ACCOUNTS ===" . PHP_EOL;
echo PHP_EOL;

// Step 1: Blacklist exhausted director NIN
echo "Step 1: Blacklisting exhausted director_nin..." . PHP_EOL;
$company = App\Models\Company::find(17);
$company->kyc_method_blacklist = ['director_nin'];
$company->save();
echo "✅ Blacklisted director_nin for Oyitipay" . PHP_EOL;
echo PHP_EOL;

// Step 2: Create VAs for users
echo "Step 2: Creating virtual accounts..." . PHP_EOL;
$service = new App\Services\PalmPay\VirtualAccountService();

$users = [239, 250, 615, 620];
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
        echo "✅ Created VA for User {$userId}: {$user->email} - {$va->account_number}" . PHP_EOL;
        $successCount++;
    } catch (Exception $e) {
        echo "❌ Failed User {$userId}: {$e->getMessage()}" . PHP_EOL;
        $failCount++;
    }
    
    sleep(2); // Rate limiting
    echo PHP_EOL;
}

echo PHP_EOL;
echo "=== SUMMARY ===" . PHP_EOL;
echo "✅ Success: {$successCount}" . PHP_EOL;
echo "❌ Failed: {$failCount}" . PHP_EOL;
echo PHP_EOL;

// Step 3: Handle duplicate user
echo "Step 3: Checking duplicate user..." . PHP_EOL;
$user252 = App\Models\CompanyUser::find(252);
$user240 = App\Models\CompanyUser::find(240);

if ($user252 && $user240) {
    $existingVA = App\Models\VirtualAccount::where('company_user_id', 240)->first();
    if ($existingVA) {
        echo "⚠️  User 252 (rukaiyazakari77@gmail.com) is duplicate of User 240 (Rukaiyazakari77@gmail.com)" . PHP_EOL;
        echo "   Existing VA: {$existingVA->account_number}" . PHP_EOL;
        echo "   Recommendation: Delete User 252 or update their email" . PHP_EOL;
    }
}

echo PHP_EOL;
echo "=== DONE ===" . PHP_EOL;
