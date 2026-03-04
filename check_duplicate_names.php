<?php
// Quick script to check for duplicate name issue with phone number

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VirtualAccount;
use App\Models\CompanyUser;

$phone = $argv[1] ?? '07040540018';

echo "=== CHECKING ACCOUNT NAME CONSISTENCY ===\n\n";
echo "Searching for phone: $phone\n\n";

// Check CompanyUser records
$customers = CompanyUser::where('phone', $phone)->get();
echo "Found " . $customers->count() . " CompanyUser records:\n";
foreach ($customers as $customer) {
    echo "- ID: {$customer->id}, UUID: {$customer->uuid}, Name: '{$customer->first_name} {$customer->last_name}', Email: {$customer->email}, Company: {$customer->company_id}\n";
}

echo "\n";

// Check VirtualAccount records (including soft deleted)
$accounts = VirtualAccount::withTrashed()->where('customer_phone', $phone)->get();
echo "Found " . $accounts->count() . " VirtualAccount records (including deleted):\n";
foreach ($accounts as $account) {
    $status = $account->deleted_at ? '[DELETED]' : '[ACTIVE]';
    echo "- $status Account: {$account->account_number}, Name: '{$account->account_name}', Customer: '{$account->customer_name}', Company: {$account->company_id}\n";
}

echo "\n";

// Check for active accounts that might be causing confusion
$activeAccounts = VirtualAccount::where('customer_phone', $phone)->where('status', 'active')->get();
if ($activeAccounts->count() > 0) {
    echo "=== ACTIVE ACCOUNTS THAT MIGHT CAUSE NAME CONFUSION ===\n";
    foreach ($activeAccounts as $account) {
        echo "❗ Active account: {$account->account_number}\n";
        echo "   Shows name: '{$account->account_name}'\n";
        echo "   Customer name: '{$account->customer_name}'\n";
        echo "   Company: {$account->company_id}\n";
        echo "   Created: {$account->created_at}\n\n";
    }
    
    echo "💡 SOLUTION FOR DEVELOPMENT:\n";
    echo "   Run: php cleanup_test_accounts.php $phone\n";
    echo "   Then create fresh test accounts with unique phone numbers\n\n";
}

echo "=== DEVELOPMENT BEST PRACTICES ===\n";
echo "1. Use unique phone numbers for each test (e.g., 0704054001X where X increments)\n";
echo "2. Don't reuse phone numbers in development testing\n";
echo "3. Use the cleanup script when needed: php cleanup_test_accounts.php <phone>\n";
echo "4. In production, this deduplication prevents duplicate accounts (good!)\n\n";

echo "=== DONE ===\n";