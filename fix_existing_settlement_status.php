<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Carbon\Carbon;

echo "=== FIXING EXISTING SETTLEMENT STATUS ===\n\n";

// Get today's transactions that are marked as 'settled' but should be 'unsettled'
$now = Carbon::now('Africa/Lagos');
$startOfDay = $now->copy()->startOfDay();

echo "Looking for transactions from today ({$startOfDay->toDateString()}) that need fixing...\n\n";

// Find transactions that:
// 1. Are from today
// 2. Are va_deposit type
// 3. Are marked as 'settled'
// 4. Are NOT in settlement_queue (meaning they were incorrectly processed)
$transactions = DB::table('transactions')
    ->leftJoin('settlement_queue', 'transactions.id', '=', 'settlement_queue.transaction_id')
    ->leftJoin('virtual_accounts', 'transactions.virtual_account_id', '=', 'virtual_accounts.id')
    ->where('transactions.transaction_type', 'va_deposit')
    ->where('transactions.status', 'success')
    ->where('transactions.settlement_status', 'settled')
    ->where('transactions.created_at', '>=', $startOfDay)
    ->whereNull('settlement_queue.id') // Not in settlement queue
    ->whereNotNull('virtual_accounts.company_user_id') // Is a CLIENT account (not master account)
    ->select('transactions.*', 'virtual_accounts.company_user_id')
    ->get();

if ($transactions->isEmpty()) {
    echo "✅ No transactions need fixing. All good!\n";
    exit(0);
}

echo "Found {$transactions->count()} transaction(s) that need fixing:\n\n";

foreach ($transactions as $tx) {
    echo "Transaction ID: {$tx->id}\n";
    echo "  Reference: {$tx->reference}\n";
    echo "  Amount: ₦{$tx->amount}\n";
    echo "  Net Amount: ₦{$tx->net_amount}\n";
    echo "  Created: {$tx->created_at}\n";
    echo "  Current Status: {$tx->settlement_status}\n";
    echo "\n";
}

echo "Do you want to fix these transactions? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$answer = trim($line);
fclose($handle);

if (strtolower($answer) !== 'yes') {
    echo "\nOperation cancelled.\n";
    exit(0);
}

echo "\nFixing transactions...\n\n";

DB::beginTransaction();

try {
    $fixed = 0;
    
    foreach ($transactions as $tx) {
        // Get settlement configuration
        $settings = DB::table('settings')->first();
        $company = DB::table('companies')->where('id', $tx->company_id)->first();
        
        $settlementEnabled = $settings && property_exists($settings, 'auto_settlement_enabled') && $settings->auto_settlement_enabled;
        $useCustomSettlement = $company && property_exists($company, 'custom_settlement_enabled') && $company->custom_settlement_enabled;
        
        if (!$settlementEnabled) {
            echo "⚠️  Auto settlement is disabled. Skipping transaction {$tx->id}\n";
            continue;
        }
        
        // Get settlement configuration
        $delayHours = $useCustomSettlement ?
            (int) ($company->custom_settlement_delay_hours ?? 24) :
            (int) ($settings->settlement_delay_hours ?? 24);
        
        $skipWeekends = (bool) ($settings->settlement_skip_weekends ?? true);
        $skipHolidays = (bool) ($settings->settlement_skip_holidays ?? true);
        $settlementTime = $settings->settlement_time ?? '02:00:00';
        
        // Calculate settlement date
        $transactionDate = Carbon::parse($tx->created_at, 'Africa/Lagos');
        $scheduledDate = \App\Console\Commands\ProcessSettlements::calculateSettlementDate(
            $transactionDate,
            $delayHours,
            $skipWeekends,
            $skipHolidays,
            $settlementTime
        );
        
        // Update transaction to unsettled
        DB::table('transactions')
            ->where('id', $tx->id)
            ->update([
                'settlement_status' => 'unsettled',
                'updated_at' => now(),
            ]);
        
        // Add to settlement queue
        DB::table('settlement_queue')->insert([
            'company_id' => $tx->company_id,
            'transaction_id' => $tx->id,
            'amount' => $tx->net_amount,
            'status' => 'pending',
            'transaction_date' => $transactionDate,
            'scheduled_settlement_date' => $scheduledDate,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        echo "✅ Fixed transaction {$tx->id}\n";
        echo "   Settlement Status: settled → unsettled\n";
        echo "   Scheduled Settlement: {$scheduledDate->toDateTimeString()}\n";
        echo "\n";
        
        $fixed++;
    }
    
    DB::commit();
    
    echo "\n=== SUMMARY ===\n";
    echo "Fixed {$fixed} transaction(s)\n";
    echo "These transactions will now be processed by the T+1 settlement cron job\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: {$e->getMessage()}\n";
    exit(1);
}

echo "\n=== END ===\n";
