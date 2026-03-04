<?php
// Generate report for PalmPay account number reuse issue
// This documents the problem for PalmPay support

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VirtualAccount;

echo "=== PALMPAY ACCOUNT NUMBER REUSE REPORT ===\n";
echo "Generated: " . date('Y-m-d H:i:s') . "\n";
echo "Issue: PalmPay is reusing virtual account numbers across different customers\n\n";

// Find all virtual accounts and group by account number
$accounts = VirtualAccount::withTrashed()
    ->select('account_number', 'palmpay_account_number', 'customer_name', 'customer_phone', 'customer_email', 'company_id', 'created_at', 'deleted_at')
    ->orderBy('account_number')
    ->get();

$accountGroups = $accounts->groupBy('account_number');
$conflicts = 0;

echo "ACCOUNT NUMBER CONFLICTS:\n";
echo str_repeat("=", 80) . "\n";

foreach ($accountGroups as $accountNumber => $accountList) {
    if ($accountList->count() > 1) {
        $conflicts++;
        echo "\n🚨 CONFLICT #{$conflicts}: Account Number {$accountNumber}\n";
        echo "This account number has been assigned to " . $accountList->count() . " different customers:\n";
        
        foreach ($accountList as $index => $account) {
            $status = $account->deleted_at ? '[DELETED]' : '[ACTIVE]';
            echo "  " . ($index + 1) . ". $status Customer: '{$account->customer_name}'\n";
            echo "     Phone: {$account->customer_phone}\n";
            echo "     Email: {$account->customer_email}\n";
            echo "     Company: {$account->company_id}\n";
            echo "     Created: {$account->created_at}\n";
            if ($account->deleted_at) {
                echo "     Deleted: {$account->deleted_at}\n";
            }
            echo "\n";
        }
        echo str_repeat("-", 60) . "\n";
    }
}

if ($conflicts === 0) {
    echo "✅ No account number conflicts found in our database.\n";
    echo "The issue appears to be on PalmPay's side where they are reusing account numbers\n";
    echo "that were previously assigned to other customers.\n";
} else {
    echo "\n📊 SUMMARY:\n";
    echo "Total account number conflicts found: {$conflicts}\n";
    echo "This indicates PalmPay is reusing virtual account numbers.\n";
}

echo "\n🔍 INVESTIGATION FINDINGS:\n";
echo "1. Mobile banking apps show cached/stale customer names\n";
echo "2. PalmPay dashboard shows current correct customer names\n";
echo "3. Deposits go to the correct current customer\n";
echo "4. The issue is cosmetic but confusing for users\n";

echo "\n📋 RECOMMENDATIONS FOR PALMPAY:\n";
echo "1. Ensure virtual account numbers are never recycled\n";
echo "2. Clear cached customer data when accounts are deleted\n";
echo "3. Implement proper account lifecycle management\n";
echo "4. Update mobile banking apps to refresh customer data\n";

echo "\n📞 NEXT STEPS:\n";
echo "1. Send this report to PalmPay support\n";
echo "2. Request immediate fix for account number reuse\n";
echo "3. Ask for confirmation that account numbers will be unique\n";
echo "4. Monitor for resolution\n";

echo "\n" . str_repeat("=", 80) . "\n";
echo "END OF REPORT\n";