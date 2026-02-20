<?php
/**
 * Reset System for Fresh Testing
 * 
 * This script will:
 * 1. Clear all transactions
 * 2. Reset all company balances to zero
 * 3. Clear webhook logs
 * 4. Clear settlement queue
 * 5. Preserve company and user data
 * 
 * Run on server: php RESET_FOR_TESTING.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "========================================\n";
echo "RESET SYSTEM FOR FRESH TESTING\n";
echo "========================================\n";
echo "\n";

echo "⚠️  WARNING: This will delete ALL transactions and reset balances!\n";
echo "⚠️  Company and user data will be preserved.\n";
echo "\n";
echo "Are you sure you want to continue? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
if (trim($line) != 'yes') {
    echo "Aborted.\n";
    exit;
}
fclose($handle);

echo "\n";
echo "Starting reset process...\n";
echo "\n";

try {
    DB::beginTransaction();
    
    // 1. Clear transactions table
    echo "1. Clearing transactions table...\n";
    $transactionCount = DB::table('transactions')->count();
    DB::table('transactions')->truncate();
    echo "   ✅ Deleted {$transactionCount} transactions\n";
    echo "\n";
    
    // 2. Clear transaction_status_logs table
    echo "2. Clearing transaction status logs...\n";
    $statusLogCount = DB::table('transaction_status_logs')->count();
    DB::table('transaction_status_logs')->truncate();
    echo "   ✅ Deleted {$statusLogCount} status log entries\n";
    echo "\n";
    
    // 3. Clear webhook_events table
    echo "3. Clearing webhook events...\n";
    $webhookCount = DB::table('webhook_events')->count();
    DB::table('webhook_events')->truncate();
    echo "   ✅ Deleted {$webhookCount} webhook events\n";
    echo "\n";
    
    // 4. Clear palmpay_webhooks table (legacy)
    echo "4. Clearing PalmPay webhook logs...\n";
    $palmpayWebhookCount = DB::table('palmpay_webhooks')->count();
    DB::table('palmpay_webhooks')->truncate();
    echo "   ✅ Deleted {$palmpayWebhookCount} PalmPay webhook logs\n";
    echo "\n";
    
    // 5. Clear settlement_queue table
    echo "5. Clearing settlement queue...\n";
    $settlementCount = DB::table('settlement_queue')->count();
    DB::table('settlement_queue')->truncate();
    echo "   ✅ Deleted {$settlementCount} settlement queue entries\n";
    echo "\n";
    
    // 6. Reset company balances to zero
    echo "6. Resetting company balances to zero...\n";
    $companies = DB::table('companies')->get();
    foreach ($companies as $company) {
        DB::table('companies')
            ->where('id', $company->id)
            ->update([
                'balance' => 0,
                'updated_at' => now()
            ]);
        echo "   ✅ Reset balance for company: {$company->name} (ID: {$company->id})\n";
    }
    echo "\n";
    
    // 7. Reset user balances to zero
    echo "7. Resetting user balances to zero...\n";
    $users = DB::table('users')->where('role', '!=', 'admin')->get();
    foreach ($users as $user) {
        DB::table('users')
            ->where('id', $user->id)
            ->update([
                'balance' => 0,
                'updated_at' => now()
            ]);
        echo "   ✅ Reset balance for user: {$user->username} (ID: {$user->id})\n";
    }
    echo "\n";
    
    // 8. Clear API request logs (optional - keeps last 100)
    echo "8. Clearing old API request logs...\n";
    $apiLogCount = DB::table('api_request_logs')->count();
    if ($apiLogCount > 100) {
        $keepIds = DB::table('api_request_logs')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->pluck('id');
        
        $deletedLogs = DB::table('api_request_logs')
            ->whereNotIn('id', $keepIds)
            ->delete();
        
        echo "   ✅ Deleted {$deletedLogs} old API logs (kept last 100)\n";
    } else {
        echo "   ℹ️  Only {$apiLogCount} API logs found, keeping all\n";
    }
    echo "\n";
    
    DB::commit();
    
    echo "========================================\n";
    echo "✅ RESET COMPLETE!\n";
    echo "========================================\n";
    echo "\n";
    echo "Summary:\n";
    echo "  • Transactions deleted: {$transactionCount}\n";
    echo "  • Status logs deleted: {$statusLogCount}\n";
    echo "  • Webhook events deleted: {$webhookCount}\n";
    echo "  • PalmPay webhooks deleted: {$palmpayWebhookCount}\n";
    echo "  • Settlement queue cleared: {$settlementCount}\n";
    echo "  • Companies reset: " . count($companies) . "\n";
    echo "  • Users reset: " . count($users) . "\n";
    echo "\n";
    echo "Preserved:\n";
    echo "  ✅ Company accounts\n";
    echo "  ✅ User accounts\n";
    echo "  ✅ Virtual accounts\n";
    echo "  ✅ API credentials\n";
    echo "  ✅ Settings\n";
    echo "\n";
    echo "Next Steps:\n";
    echo "1. Clear Laravel caches:\n";
    echo "   php artisan cache:clear\n";
    echo "   php artisan view:clear\n";
    echo "   php artisan config:clear\n";
    echo "\n";
    echo "2. Test deposit flow:\n";
    echo "   - Send money to PalmPay virtual account\n";
    echo "   - Check webhook logs (Admin > Webhook Logs)\n";
    echo "   - Verify transaction appears in RA Transactions\n";
    echo "   - Check balance updated in Wallet\n";
    echo "\n";
    echo "3. Test transfer flow:\n";
    echo "   - Make a transfer from dashboard\n";
    echo "   - Check transaction in RA Transactions\n";
    echo "   - Verify balance deducted\n";
    echo "   - Check settlement status\n";
    echo "\n";
    echo "System is ready for fresh testing! 🚀\n";
    echo "\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n";
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Reset failed. Database rolled back.\n";
    echo "\n";
    exit(1);
}
