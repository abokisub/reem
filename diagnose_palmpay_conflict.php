<?php
// Diagnose PalmPay account conflict
// This script checks for conflicts between our database and PalmPay's records

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VirtualAccount;
use App\Models\CompanyUser;

$phone = $argv[1] ?? '07040540018';

echo "=== PALMPAY ACCOUNT CONFLICT DIAGNOSIS ===\n";
echo "Phone: $phone\n\n";

// 1. Check our database records
echo "1. OUR DATABASE RECORDS:\n";
$companyUsers = CompanyUser::where('phone', $phone)->get();
echo "CompanyUser records: " . $companyUsers->count() . "\n";
foreach ($companyUsers as $user) {
    echo "- ID: {$user->id}, Name: '{$user->first_name} {$user->last_name}', Company: {$user->company_id}\n";
}

$virtualAccounts = VirtualAccount::withTrashed()->where('customer_phone', $phone)->get();
echo "\nVirtualAccount records (including deleted): " . $virtualAccounts->count() . "\n";
foreach ($virtualAccounts as $account) {
    $status = $account->deleted_at ? '[DELETED]' : '[ACTIVE]';
    echo "- $status Account: {$account->account_number}, Name: '{$account->customer_name}', Company: {$account->company_id}\n";
    echo "  Created: {$account->created_at}, Deleted: " . ($account->deleted_at ?? 'N/A') . "\n";
}

// 2. Check for account number conflicts
echo "\n2. ACCOUNT NUMBER ANALYSIS:\n";
$accountNumber = '6607951926'; // From the screenshot
$conflictingAccounts = VirtualAccount::withTrashed()
    ->where('account_number', $accountNumber)
    ->orWhere('palmpay_account_number', $accountNumber)
    ->get();

echo "Accounts using number $accountNumber: " . $conflictingAccounts->count() . "\n";
foreach ($conflictingAccounts as $account) {
    $status = $account->deleted_at ? '[DELETED]' : '[ACTIVE]';
    echo "- $status Customer: '{$account->customer_name}', Phone: {$account->customer_phone}, Company: {$account->company_id}\n";
    echo "  Created: {$account->created_at}, Deleted: " . ($account->deleted_at ?? 'N/A') . "\n";
}

// 3. Check for Maryam Ahmad records
echo "\n3. MARYAM AHMAD RECORDS:\n";
$maryamAccounts = VirtualAccount::withTrashed()
    ->where('customer_name', 'LIKE', '%Maryam%')
    ->orWhere('customer_name', 'LIKE', '%Ahmad%')
    ->get();

echo "Accounts with 'Maryam' or 'Ahmad': " . $maryamAccounts->count() . "\n";
foreach ($maryamAccounts as $account) {
    $status = $account->deleted_at ? '[DELETED]' : '[ACTIVE]';
    echo "- $status Account: {$account->account_number}, Name: '{$account->customer_name}'\n";
    echo "  Phone: {$account->customer_phone}, Company: {$account->company_id}\n";
    echo "  Created: {$account->created_at}, Deleted: " . ($account->deleted_at ?? 'N/A') . "\n";
}

echo "\n=== DIAGNOSIS COMPLETE ===\n";
echo "If account number $accountNumber appears multiple times or belongs to Maryam,\n";
echo "this explains why the new registration is showing the wrong name.\n";
echo "\nPossible causes:\n";
echo "1. PalmPay is reusing deleted account numbers\n";
echo "2. Our deduplication logic is finding old deleted records\n";
echo "3. Account number collision between different customers\n";