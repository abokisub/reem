<?php
// Development helper to clean up test accounts by phone number
// USE ONLY IN DEVELOPMENT/STAGING - NEVER IN PRODUCTION

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VirtualAccount;
use App\Models\CompanyUser;

if (app()->environment('production')) {
    die("❌ ERROR: This script cannot run in production environment!\n");
}

$phone = $argv[1] ?? null;
if (!$phone) {
    die("Usage: php cleanup_test_accounts.php <phone_number>\nExample: php cleanup_test_accounts.php 07040540018\n");
}

echo "=== CLEANING UP TEST ACCOUNTS FOR PHONE: $phone ===\n\n";

// 1. Soft delete virtual accounts
$virtualAccounts = VirtualAccount::where('customer_phone', $phone)->get();
echo "Found " . $virtualAccounts->count() . " virtual accounts to clean up:\n";

foreach ($virtualAccounts as $account) {
    echo "- Deleting account: {$account->account_number} ({$account->customer_name})\n";
    $account->delete(); // Soft delete
}

// 2. Delete company users (your database records)
$companyUsers = CompanyUser::where('phone', $phone)->get();
echo "\nFound " . $companyUsers->count() . " company users to clean up:\n";

foreach ($companyUsers as $user) {
    echo "- Deleting user: {$user->first_name} {$user->last_name} (Company: {$user->company_id})\n";
    $user->delete();
}

echo "\n✅ Cleanup completed! You can now create fresh test accounts with phone: $phone\n";
echo "\n⚠️  REMEMBER: Use unique phone numbers for each test to avoid this issue!\n";