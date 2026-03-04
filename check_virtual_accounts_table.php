<?php
// Check what's actually in the virtual_accounts table
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VirtualAccount;
use App\Models\CompanyUser;

echo "=== VIRTUAL ACCOUNTS TABLE INVESTIGATION ===\n\n";

// Check for the problematic account number
$accountNumber = '6662822179';
echo "1. SEARCHING FOR ACCOUNT: $accountNumber\n";

$accounts = VirtualAccount::withTrashed()
    ->where('account_number', $accountNumber)
    ->orWhere('palmpay_account_number', $accountNumber)
    ->get();

echo "Found " . $accounts->count() . " records:\n";
foreach ($accounts as $account) {
    $status = $account->deleted_at ? '[DELETED]' : '[ACTIVE]';
    echo "- $status ID: {$account->id}\n";
    echo "  Company: {$account->company_id}\n";
    echo "  User ID: {$account->user_id}\n";
    echo "  Customer: {$account->customer_name}\n";
    echo "  Phone: {$account->customer_phone}\n";
    echo "  Email: {$account->customer_email}\n";
    echo "  Created: {$account->created_at}\n";
    if ($account->deleted_at) {
        echo "  Deleted: {$account->deleted_at}\n";
    }
    echo "\n";
}

// Check for Nana Aisha Bello specifically
echo "2. SEARCHING FOR 'NANA AISHA BELLO':\n";
$nanaAccounts = VirtualAccount::withTrashed()
    ->where('customer_name', 'LIKE', '%Nana%')
    ->orWhere('customer_name', 'LIKE', '%Aisha%')
    ->orWhere('customer_name', 'LIKE', '%Bello%')
    ->get();

echo "Found " . $nanaAccounts->count() . " accounts with Nana/Aisha/Bello:\n";
foreach ($nanaAccounts as $account) {
    $status = $account->deleted_at ? '[DELETED]' : '[ACTIVE]';
    echo "- $status Account: {$account->account_number}\n";
    echo "  Customer: {$account->customer_name}\n";
    echo "  Company: {$account->company_id}\n";
    echo "  Phone: {$account->customer_phone}\n";
    echo "  Created: {$account->created_at}\n";
    echo "\n";
}

// Check Company 4 (KoboPoint) recent accounts
echo "3. RECENT ACCOUNTS FOR COMPANY 4 (KOBOPOINT):\n";
$recentAccounts = VirtualAccount::where('company_id', 4)
    ->where('created_at', '>=', now()->subDays(1))
    ->orderBy('created_at', 'desc')
    ->get();

echo "Found " . $recentAccounts->count() . " recent accounts:\n";
foreach ($recentAccounts as $account) {
    echo "- Account: {$account->account_number}\n";
    echo "  Customer: {$account->customer_name}\n";
    echo "  Phone: {$account->customer_phone}\n";
    echo "  Created: {$account->created_at}\n";
    echo "\n";
}

echo "=== ANALYSIS ===\n";
echo "This will show us:\n";
echo "1. If account 6662822179 exists in our virtual_accounts table\n";
echo "2. Who it's assigned to\n";
echo "3. Whether there are conflicts\n";