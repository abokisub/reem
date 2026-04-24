<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use App\Models\GlobalKycPool;
use Illuminate\Support\Facades\DB;

echo "=== Kobopoint KYC Status Check ===\n\n";

// Find Kobopoint company
$kobopoint = Company::where('name', 'LIKE', '%kobopoint%')
    ->orWhere('name', 'LIKE', '%Kobopoint%')
    ->orWhere('name', 'LIKE', '%KoboPoint%')
    ->first();

if (!$kobopoint) {
    echo "❌ Kobopoint company not found!\n";
    echo "Searching all companies...\n\n";
    
    $companies = Company::select('id', 'name')->get();
    foreach ($companies as $company) {
        echo "ID: {$company->id} - {$company->name}\n";
    }
    exit(1);
}

echo "✓ Found Kobopoint\n";
echo "Company ID: {$kobopoint->id}\n";
echo "Company Name: {$kobopoint->name}\n";
echo "Email: {$kobopoint->email}\n\n";

echo "--- Current KYC Configuration ---\n";
echo "Director BVN: " . ($kobopoint->director_bvn ? substr($kobopoint->director_bvn, 0, 5) . '***' : '❌ Not set') . "\n";
echo "Director NIN: " . ($kobopoint->director_nin ? substr($kobopoint->director_nin, 0, 5) . '***' : '❌ Not set') . "\n";

// Check backup directors
echo "\n--- Backup Directors ---\n";
$backupCount = 0;
for ($i = 2; $i <= 10; $i++) {
    $bvnField = "backup_director_{$i}_bvn";
    $ninField = "backup_director_{$i}_nin";
    
    $hasBvn = !empty($kobopoint->$bvnField);
    $hasNin = !empty($kobopoint->$ninField);
    
    if ($hasBvn || $hasNin) {
        $backupCount++;
        echo "Backup Director #{$i}:\n";
        if ($hasBvn) echo "  BVN: " . substr($kobopoint->$bvnField, 0, 5) . "***\n";
        if ($hasNin) echo "  NIN: " . substr($kobopoint->$ninField, 0, 5) . "***\n";
    }
}

if ($backupCount === 0) {
    echo "No backup directors configured\n";
}

// Check virtual accounts
echo "\n--- Virtual Accounts Status ---\n";
$totalUsers = DB::table('company_users')
    ->where('company_id', $kobopoint->id)
    ->count();

$usersWithAccounts = DB::table('company_users')
    ->where('company_id', $kobopoint->id)
    ->whereExists(function ($query) use ($kobopoint) {
        $query->select(DB::raw(1))
            ->from('virtual_accounts')
            ->whereColumn('virtual_accounts.company_user_id', 'company_users.id')
            ->where('virtual_accounts.company_id', $kobopoint->id)
            ->where('virtual_accounts.status', 'active')
            ->whereNull('virtual_accounts.deleted_at');
    })
    ->count();

$usersWithoutAccounts = $totalUsers - $usersWithAccounts;

echo "Total Company Users: {$totalUsers}\n";
echo "Users with Virtual Accounts: {$usersWithAccounts}\n";
echo "Users WITHOUT Virtual Accounts: {$usersWithoutAccounts}\n";

// Check global KYC pool
echo "\n--- Global KYC Pool Status ---\n";
$availableBvn = GlobalKycPool::available()->byType('bvn')->count();
$availableNin = GlobalKycPool::available()->byType('nin')->count();
$totalBvn = GlobalKycPool::byType('bvn')->count();
$totalNin = GlobalKycPool::byType('nin')->count();

echo "Available BVN: {$availableBvn} / {$totalBvn}\n";
echo "Available NIN: {$availableNin} / {$totalNin}\n";

// Show recommended action
echo "\n=== Recommended Action ===\n";
if ($usersWithoutAccounts > 0) {
    if ($availableNin > 0 || $availableBvn > 0) {
        $recommendedType = $availableNin > 0 ? 'nin' : 'bvn';
        echo "✓ Run the following command to fix:\n\n";
        echo "php artisan company:assign-fresh-kyc {$kobopoint->id} --type={$recommendedType} --regenerate\n\n";
        echo "This will:\n";
        echo "1. Assign a fresh {$recommendedType} from the global pool to Kobopoint\n";
        echo "2. Regenerate virtual accounts for {$usersWithoutAccounts} users\n";
    } else {
        echo "❌ No available KYC in global pool!\n";
        echo "Please add BVN/NIN to the global pool first.\n";
    }
} else {
    echo "✓ All users have virtual accounts. No action needed.\n";
}

echo "\n";
