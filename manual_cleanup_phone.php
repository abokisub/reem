<?php
// Manual cleanup script for specific phone numbers
// Can be run in production with confirmation

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VirtualAccount;
use App\Models\CompanyUser;

$phone = $argv[1] ?? null;
$confirm = $argv[2] ?? null;

if (!$phone) {
    die("Usage: php manual_cleanup_phone.php <phone_number> <CONFIRM>\nExample: php manual_cleanup_phone.php 07040540018 CONFIRM\n");
}

if ($confirm !== 'CONFIRM') {
    echo "⚠️  WARNING: This will delete all accounts associated with phone: $phone\n";
    echo "This action cannot be undone!\n\n";
    echo "To proceed, run: php manual_cleanup_phone.php $phone CONFIRM\n";
    exit(1);
}

echo "=== MANUAL CLEANUP FOR PHONE: $phone ===\n\n";

// 1. Find and soft delete virtual accounts
$virtualAccounts = VirtualAccount::where('customer_phone', $phone)->get();
echo "Found " . $virtualAccounts->count() . " virtual accounts:\n";

foreach ($virtualAccounts as $account) {
    echo "- Deleting account: {$account->account_number} ({$account->customer_name}) - Company: {$account->company_id}\n";
    $account->delete(); // Soft delete
}

// 2. Delete company users
$companyUsers = CompanyUser::where('phone', $phone)->get();
echo "\nFound " . $companyUsers->count() . " company users:\n";

foreach ($companyUsers as $user) {
    echo "- Deleting user: {$user->first_name} {$user->last_name} - Company: {$user->company_id}\n";
    $user->delete();
}

echo "\n✅ Cleanup completed for phone: $phone\n";
echo "Developer can now create fresh test accounts with this phone number.\n";