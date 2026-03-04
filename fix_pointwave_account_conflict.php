<?php
// Fix PalmPay account conflicts in KoboPoint app
// This addresses the issue where PalmPay API returns accounts already assigned to other users

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

$phone = $argv[1] ?? '07040540018';
$confirm = $argv[2] ?? null;

if ($confirm !== 'CONFIRM') {
    echo "⚠️  WARNING: This will fix PalmPay account conflicts\n";
    echo "This will remove conflicting account assignments and force fresh account creation\n\n";
    echo "To proceed, run: php fix_pointwave_account_conflict.php $phone CONFIRM\n";
    exit(1);
}

echo "=== FIXING PALMPAY ACCOUNT CONFLICTS ===\n";
echo "Phone: $phone\n\n";

// 1. Find users with this phone number
$users = User::where('phone', $phone)->get();
echo "Found " . $users->count() . " users with phone $phone:\n";

foreach ($users as $user) {
    echo "- User ID: {$user->id}, Username: {$user->username}\n";
    echo "  PalmPay Account: " . ($user->palmpay_account_number ?? 'None') . "\n";
    echo "  PalmPay Customer ID: " . ($user->palmpay_customer_id ?? 'None') . "\n";
    
    if ($user->palmpay_account_number) {
        // Check if this account number is used by other users
        $conflictingUsers = User::where('palmpay_account_number', $user->palmpay_account_number)
            ->where('id', '!=', $user->id)
            ->get();
            
        if ($conflictingUsers->count() > 0) {
            echo "  🚨 CONFLICT DETECTED: Account {$user->palmpay_account_number} is also assigned to:\n";
            foreach ($conflictingUsers as $conflictUser) {
                echo "    - User ID: {$conflictUser->id}, Username: {$conflictUser->username}\n";
            }
            
            // Clear the conflicting account from this user
            echo "  Clearing PalmPay account from user {$user->username}...\n";
            $user->update([
                'palmpay_account_number' => null,
                'palmpay_account_name' => null,
                'palmpay_bank_name' => null,
                'palmpay_customer_id' => null,
            ]);
            echo "  ✅ Cleared conflicting account assignment\n";
        } else {
            echo "  ✅ No conflicts found for this account\n";
        }
    }
    echo "\n";
}

// 2. Check for duplicate account numbers across all users
echo "2. CHECKING FOR DUPLICATE PALMPAY ACCOUNT NUMBERS:\n";
$duplicateAccounts = DB::table('users')
    ->select('palmpay_account_number', DB::raw('COUNT(*) as count'))
    ->whereNotNull('palmpay_account_number')
    ->groupBy('palmpay_account_number')
    ->having('count', '>', 1)
    ->get();

echo "Found " . $duplicateAccounts->count() . " duplicate account numbers:\n";
foreach ($duplicateAccounts as $duplicate) {
    echo "- Account {$duplicate->palmpay_account_number} assigned to {$duplicate->count} users\n";
    
    $usersWithAccount = User::where('palmpay_account_number', $duplicate->palmpay_account_number)->get();
    foreach ($usersWithAccount as $user) {
        echo "  - User ID: {$user->id}, Username: {$user->username}, Phone: {$user->phone}\n";
    }
    echo "\n";
}

echo "✅ CONFLICT RESOLUTION COMPLETE\n";
echo "\nNext steps:\n";
echo "1. Users with cleared accounts will get fresh accounts on next login/transaction\n";
echo "2. PalmPay API should create new unique accounts\n";
echo "3. Monitor logs to ensure no more conflicts occur\n";
echo "\nIf conflicts persist, contact PointWave support about their API returning existing account numbers.\n";