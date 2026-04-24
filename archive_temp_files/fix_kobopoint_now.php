<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use App\Services\GlobalKycService;
use App\Services\PalmPay\VirtualAccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

echo "=== Emergency Fix for Kobopoint ===\n\n";

$kobopoint = Company::find(4);

if (!$kobopoint) {
    echo "❌ Kobopoint not found\n";
    exit(1);
}

echo "Step 1: Checking current KYC status\n";
echo "─────────────────────────────────────\n";
echo "Director BVN: " . ($kobopoint->director_bvn ? substr($kobopoint->director_bvn, 0, 5) . '***' : 'Not set') . "\n";
echo "Director NIN: " . ($kobopoint->director_nin ? substr($kobopoint->director_nin, 0, 5) . '***' : 'Not set') . "\n";
echo "Backup Director 2 NIN: " . ($kobopoint->backup_director_2_nin ? substr($kobopoint->backup_director_2_nin, 0, 5) . '***' : 'Not set') . "\n\n";

// Step 2: Assign fresh NIN if not already assigned
if (empty($kobopoint->backup_director_2_nin)) {
    echo "Step 2: Assigning fresh NIN from global pool\n";
    echo "─────────────────────────────────────\n";
    
    $globalKycService = new GlobalKycService();
    $selectedKyc = $globalKycService->selectOptimalGlobalKyc('nin');
    
    if (!$selectedKyc) {
        echo "❌ No available NIN in global pool\n";
        exit(1);
    }
    
    echo "Selected NIN: " . substr($selectedKyc->kyc_number, 0, 5) . "***\n";
    echo "Usage Count: {$selectedKyc->usage_count}\n";
    echo "Success Rate: " . round($selectedKyc->success_rate, 2) . "%\n\n";
    
    $kobopoint->update(['backup_director_2_nin' => $selectedKyc->kyc_number]);
    echo "✓ Assigned to backup_director_2_nin\n\n";
} else {
    echo "Step 2: Fresh NIN already assigned\n";
    echo "─────────────────────────────────────\n";
    echo "✓ Backup Director 2 NIN: " . substr($kobopoint->backup_director_2_nin, 0, 5) . "***\n\n";
}

// Step 3: Blacklist old director_nin
echo "Step 3: Blacklisting old director_nin\n";
echo "─────────────────────────────────────\n";

$blacklistKey = "kyc_blacklist_company_{$kobopoint->id}";
$blacklist = Cache::get($blacklistKey, []);

$blacklist['director_nin'] = [
    'blacklisted_at' => now()->toISOString(),
    'reason' => 'Hit PalmPay license number limit - use backup_director_2_nin instead',
    'expires_at' => now()->addDays(30)->toISOString()
];

Cache::put($blacklistKey, $blacklist, now()->addDays(30));
echo "✓ Blacklisted director_nin for 30 days\n";
echo "✓ System will now use backup_director_2_nin\n\n";

// Step 4: Regenerate missing accounts
echo "Step 4: Regenerating missing virtual accounts\n";
echo "─────────────────────────────────────\n";

$companyUsers = DB::table('company_users')
    ->where('company_id', $kobopoint->id)
    ->whereNotExists(function ($query) use ($kobopoint) {
        $query->select(DB::raw(1))
            ->from('virtual_accounts')
            ->whereColumn('virtual_accounts.company_user_id', 'company_users.id')
            ->where('virtual_accounts.company_id', $kobopoint->id)
            ->where('virtual_accounts.status', 'active')
            ->whereNull('virtual_accounts.deleted_at');
    })
    ->get();

echo "Found {$companyUsers->count()} users without virtual accounts\n\n";

if ($companyUsers->count() === 0) {
    echo "✓ All users have virtual accounts!\n";
    exit(0);
}

$virtualAccountService = new VirtualAccountService();
$successCount = 0;
$failCount = 0;

foreach ($companyUsers as $companyUser) {
    try {
        // The user_id in company_users is actually a hash/uuid that matches users.id
        $userId = $companyUser->user_id;
        
        if (!$userId) {
            echo "⚠️  Company user {$companyUser->id} has no user_id\n";
            $failCount++;
            continue;
        }

        // Find user by ID (which is a hash)
        $user = DB::table('users')->where('id', $userId)->first();
        
        if (!$user) {
            echo "⚠️  User {$userId} not found\n";
            $failCount++;
            continue;
        }

        echo "Creating account for: {$user->email}... ";

        $customerData = [
            'name' => $user->name ?? 'Customer',
            'email' => $user->email,
            'phone' => $user->phone,
            'account_type' => 'static'
        ];

        $virtualAccount = $virtualAccountService->createVirtualAccount(
            $kobopoint->id,
            $userId,
            $customerData,
            '100033',
            $companyUser->id
        );

        echo "✓ {$virtualAccount->account_number}\n";
        $successCount++;
        
    } catch (\Exception $e) {
        echo "✗ {$e->getMessage()}\n";
        $failCount++;
    }
}

echo "\n";
echo "═══════════════════════════════════════\n";
echo "SUMMARY\n";
echo "═══════════════════════════════════════\n";
echo "Success: {$successCount}\n";
echo "Failed: {$failCount}\n";
echo "\n";

if ($failCount > 0) {
    echo "⚠️  Some accounts failed. Check logs:\n";
    echo "tail -50 storage/logs/laravel.log\n";
} else {
    echo "✓ All accounts created successfully!\n";
}
