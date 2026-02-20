<?php

/**
 * RESET SYSTEM FOR FRESH TESTING
 * 
 * This script clears all transactions and resets balances to allow fresh testing
 * 
 * WARNING: This will delete ALL transaction data!
 * Only use this on development/testing environments!
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Carbon\Carbon;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║         RESET SYSTEM FOR FRESH TESTING                     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "⚠️  WARNING: This will DELETE ALL transaction data!\n";
echo "⚠️  This includes:\n";
echo "   - All transactions\n";
echo "   - All settlement queue entries\n";
echo "   - All webhook logs\n";
echo "   - All PalmPay webhooks\n";
echo "   - All ledger entries\n";
echo "   - Reset all company wallet balances to ₦199.00\n";
echo "   - Reset all system wallet balances to ₦0.00\n\n";

echo "Are you ABSOLUTELY SURE you want to continue? (type 'YES' to confirm): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$answer = trim($line);
fclose($handle);

if ($answer !== 'YES') {
    echo "\n❌ Operation cancelled. No changes made.\n";
    exit(0);
}

echo "\n🔄 Starting reset process...\n\n";

DB::beginTransaction();

try {
    // 1. Delete all transactions
    $transactionCount = DB::table('transactions')->count();
    DB::table('transactions')->delete();
    echo "✅ Deleted {$transactionCount} transactions\n";
    
    // 2. Delete settlement queue
    $settlementCount = DB::table('settlement_queue')->count();
    DB::table('settlement_queue')->delete();
    echo "✅ Deleted {$settlementCount} settlement queue entries\n";
    
    // 3. Delete webhook logs
    if (Schema::hasTable('company_webhook_logs')) {
        $webhookLogCount = DB::table('company_webhook_logs')->count();
        DB::table('company_webhook_logs')->delete();
        echo "✅ Deleted {$webhookLogCount} company webhook logs\n";
    }
    
    // 4. Delete PalmPay webhooks
    if (Schema::hasTable('palmpay_webhooks')) {
        $palmpayWebhookCount = DB::table('palmpay_webhooks')->count();
        DB::table('palmpay_webhooks')->delete();
        echo "✅ Deleted {$palmpayWebhookCount} PalmPay webhooks\n";
    }
    
    // 5. Delete ledger entries
    if (Schema::hasTable('ledger_entries')) {
        $ledgerCount = DB::table('ledger_entries')->count();
        DB::table('ledger_entries')->delete();
        echo "✅ Deleted {$ledgerCount} ledger entries\n";
    }
    
    // 6. Delete transaction status logs
    if (Schema::hasTable('transaction_status_logs')) {
        $statusLogCount = DB::table('transaction_status_logs')->count();
        DB::table('transaction_status_logs')->delete();
        echo "✅ Deleted {$statusLogCount} transaction status logs\n";
    }
    
    // 7. Delete failed transactions
    if (Schema::hasTable('failed_transactions')) {
        $failedCount = DB::table('failed_transactions')->count();
        DB::table('failed_transactions')->delete();
        echo "✅ Deleted {$failedCount} failed transactions\n";
    }
    
    // 8. Reset company wallet balances to ₦199.00 (initial balance)
    $companies = DB::table('companies')->get();
    foreach ($companies as $company) {
        DB::table('company_wallets')
            ->where('company_id', $company->id)
            ->update([
                'balance' => 199.00,
                'ledger_balance' => 199.00,
                'pending_balance' => 0.00,
                'updated_at' => now(),
            ]);
    }
    echo "✅ Reset {$companies->count()} company wallet balances to ₦199.00\n";
    
    // 9. Reset system wallets to ₦0.00
    if (Schema::hasTable('system_wallets')) {
        $systemWallets = DB::table('system_wallets')->get();
        foreach ($systemWallets as $wallet) {
            DB::table('system_wallets')
                ->where('id', $wallet->id)
                ->update([
                    'balance' => 0.00,
                    'updated_at' => now(),
                ]);
        }
        echo "✅ Reset {$systemWallets->count()} system wallet balances to ₦0.00\n";
    }
    
    DB::commit();
    
    echo "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║                  RESET COMPLETE! ✅                        ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n\n";
    
    echo "📊 Summary:\n";
    echo "   - All transactions deleted\n";
    echo "   - All settlement queues cleared\n";
    echo "   - All webhook logs cleared\n";
    echo "   - All ledger entries cleared\n";
    echo "   - Company wallets reset to ₦199.00\n";
    echo "   - System wallets reset to ₦0.00\n\n";
    
    echo "🎯 System is now ready for fresh testing!\n";
    echo "   You can now make a test deposit to verify the settlement status fix.\n\n";
    
    // Show current state
    echo "📋 Current State:\n";
    $companies = DB::table('companies')
        ->join('company_wallets', 'companies.id', '=', 'company_wallets.company_id')
        ->select('companies.name', 'company_wallets.balance')
        ->get();
    
    foreach ($companies as $company) {
        echo "   - {$company->name}: ₦" . number_format($company->balance, 2) . "\n";
    }
    
    echo "\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: {$e->getMessage()}\n";
    echo "No changes were made.\n";
    exit(1);
}
