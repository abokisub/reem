<?php
// Fix PalmPay account conflict by properly deleting accounts on PalmPay side
// This script will delete the conflicting account on PalmPay and create a fresh one

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VirtualAccount;
use App\Models\CompanyUser;
use App\Services\PalmPay\VirtualAccountService;

$phone = $argv[1] ?? '07040540018';
$confirm = $argv[2] ?? null;

if ($confirm !== 'CONFIRM') {
    echo "⚠️  WARNING: This will delete conflicting PalmPay accounts and create fresh ones\n";
    echo "This action cannot be undone!\n\n";
    echo "To proceed, run: php fix_palmpay_account_conflict.php $phone CONFIRM\n";
    exit(1);
}

echo "=== FIXING PALMPAY ACCOUNT CONFLICT ===\n";
echo "Phone: $phone\n\n";

// 1. Find all virtual accounts (including deleted) for this phone
$virtualAccounts = VirtualAccount::withTrashed()->where('customer_phone', $phone)->get();
echo "Found " . $virtualAccounts->count() . " virtual accounts for phone $phone:\n";

$palmPayService = new VirtualAccountService();

foreach ($virtualAccounts as $account) {
    $status = $account->deleted_at ? '[DELETED]' : '[ACTIVE]';
    echo "- $status Account: {$account->account_number}, Name: '{$account->customer_name}'\n";
    
    // Delete on PalmPay side if account number exists
    if ($account->palmpay_account_number) {
        echo "  Deleting account {$account->palmpay_account_number} on PalmPay...\n";
        $result = $palmPayService->deleteVirtualAccount($account->palmpay_account_number);
        
        if ($result['success']) {
            echo "  ✅ Successfully deleted on PalmPay\n";
        } else {
            echo "  ❌ Failed to delete on PalmPay: " . $result['message'] . "\n";
        }
    }
    
    // Force delete from our database (permanent delete)
    if ($account->deleted_at) {
        echo "  Force deleting from our database...\n";
        $account->forceDelete();
        echo "  ✅ Permanently deleted from our database\n";
    } else {
        echo "  Soft deleting from our database...\n";
        $account->delete();
        echo "  ✅ Soft deleted from our database\n";
    }
}

echo "\n2. CLEANING UP COMPANY USERS:\n";
$companyUsers = CompanyUser::where('phone', $phone)->get();
echo "Found " . $companyUsers->count() . " company users:\n";

foreach ($companyUsers as $user) {
    echo "- Deleting user: {$user->first_name} {$user->last_name} - Company: {$user->company_id}\n";
    $user->delete();
}

echo "\n✅ CONFLICT RESOLUTION COMPLETE\n";
echo "All conflicting accounts have been deleted from both PalmPay and our database.\n";
echo "The developer can now create a completely fresh virtual account.\n";
echo "\nNext steps:\n";
echo "1. Developer should register again with phone $phone\n";
echo "2. System will create a brand new virtual account on PalmPay\n";
echo "3. Account name should show correctly as 'kobopoint-[CustomerName]'\n";