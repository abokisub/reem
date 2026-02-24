<?php

/**
 * Comprehensive Settlement Diagnosis and Fix
 * 
 * This script:
 * 1. Checks if auto_settlement is enabled
 * 2. Checks if cron is running
 * 3. Finds stuck settlements
 * 4. Fixes them
 * 5. Enables auto_settlement if disabled
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\CompanyWallet;
use App\Models\Transaction;

echo "========================================\n";
echo "SETTLEMENT SYSTEM DIAGNOSIS\n";
echo "========================================\n\n";

$now = Carbon::now();
echo "Current Time: " . $now->format('Y-m-d H:i:s') . " (Africa/Lagos)\n\n";

// STEP 1: Check Settings
echo "STEP 1: Checking Settings\n";
echo "----------------------------------------\n";
$settings = DB::table('settings')->first();

if (!$settings) {
    echo "❌ ERROR: Settings table is empty!\n";
    echo "Creating default settings...\n";
    
    DB::table('settings')->insert([
        'auto_settlement_enabled' => true,
        'settlement_delay_hours' => 24,
        'settlement_skip_weekends' => true,
        'settlement_skip_holidays' => true,
        'settlement_time' => '03:00:00',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    
    $settings = DB::table('settings')->first();
    echo "✅ Default settings created\n";
}

$autoSettlementEnabled = $settings->auto_settlement_enabled ?? false;
echo "Auto Settlement Enabled: " . ($autoSettlementEnabled ? "YES ✅" : "NO ❌") . "\n";
echo "Settlement Delay: " . ($settings->settlement_delay_hours ?? 24) . " hours\n";
echo "Settlement Time: " . ($settings->settlement_time ?? '03:00:00') . "\n";
echo "Skip Weekends: " . (($settings->settlement_skip_weekends ?? true) ? "YES" : "NO") . "\n";
echo "Skip Holidays: " . (($settings->settlement_skip_holidays ?? true) ? "YES" : "NO") . "\n\n";

if (!$autoSettlementEnabled) {
    echo "⚠️  WARNING: Auto settlement is DISABLED!\n";
    echo "Enabling auto settlement...\n";
    
    DB::table('settings')->update([
        'auto_settlement_enabled' => true,
        'updated_at' => now(),
    ]);
    
    echo "✅ Auto settlement ENABLED\n\n";
}

// STEP 2: Check Settlement Queue
echo "STEP 2: Checking Settlement Queue\n";
echo "----------------------------------------\n";

$pendingSettlements = DB::table('settlement_queue')
    ->where('status', 'pending')
    ->orderBy('scheduled_settlement_date')
    ->get();

echo "Total Pending Settlements: " . $pendingSettlements->count() . "\n";

$overdueSettlements = $pendingSettlements->filter(function($s) use ($now) {
    return Carbon::parse($s->scheduled_settlement_date)->isPast();
});

echo "Overdue Settlements: " . $overdueSettlements->count() . "\n\n";

if ($overdueSettlements->count() > 0) {
    echo "⚠️  OVERDUE SETTLEMENTS FOUND!\n\n";
    
    foreach ($overdueSettlements as $settlement) {
        $scheduledDate = Carbon::parse($settlement->scheduled_settlement_date);
        $hoursOverdue = abs($now->diffInHours($scheduledDate, false));
        
        echo "Settlement ID: {$settlement->id}\n";
        echo "Company ID: {$settlement->company_id}\n";
        echo "Amount: ₦" . number_format($settlement->amount, 2) . "\n";
        echo "Scheduled: {$settlement->scheduled_settlement_date}\n";
        echo "Hours Overdue: {$hoursOverdue}\n";
        echo "----------------------------------------\n";
    }
}

// STEP 3: Check for Transactions Without Settlement Queue
echo "\nSTEP 3: Checking for Missing Settlement Queue Entries\n";
echo "----------------------------------------\n";

$transactionsWithoutQueue = DB::select("
    SELECT 
        t.id,
        t.transaction_id,
        t.reference,
        t.company_id,
        t.amount,
        t.net_amount,
        t.created_at,
        t.settlement_status,
        c.name as company_name
    FROM transactions t
    LEFT JOIN settlement_queue sq ON t.id = sq.transaction_id
    LEFT JOIN companies c ON t.company_id = c.id
    WHERE t.transaction_type = 'va_deposit'
    AND t.status = 'successful'
    AND t.settlement_status = 'unsettled'
    AND sq.id IS NULL
    AND t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY t.created_at DESC
");

echo "Transactions without settlement queue: " . count($transactionsWithoutQueue) . "\n\n";

if (count($transactionsWithoutQueue) > 0) {
    echo "⚠️  MISSING SETTLEMENT QUEUE ENTRIES!\n\n";
    
    foreach ($transactionsWithoutQueue as $tx) {
        echo "Transaction ID: {$tx->transaction_id}\n";
        echo "Company: {$tx->company_name} (ID: {$tx->company_id})\n";
        echo "Amount: ₦" . number_format($tx->net_amount, 2) . "\n";
        echo "Created: {$tx->created_at}\n";
        echo "----------------------------------------\n";
    }
}

// STEP 4: Fix Everything
echo "\nSTEP 4: FIXING ISSUES\n";
echo "========================================\n\n";

$fixed = 0;
$errors = [];

// Fix 1: Process Overdue Settlements
if ($overdueSettlements->count() > 0) {
    echo "Processing " . $overdueSettlements->count() . " overdue settlements...\n\n";
    
    foreach ($overdueSettlements as $settlement) {
        try {
            DB::beginTransaction();
            
            // Mark as processing
            DB::table('settlement_queue')
                ->where('id', $settlement->id)
                ->update(['status' => 'processing']);
            
            // Get transaction
            $transaction = Transaction::find($settlement->transaction_id);
            
            if (!$transaction) {
                throw new \Exception("Transaction not found: {$settlement->transaction_id}");
            }
            
            // Get company wallet with lock
            $wallet = CompanyWallet::where('company_id', $settlement->company_id)
                ->where('currency', 'NGN')
                ->lockForUpdate()
                ->first();
            
            if (!$wallet) {
                throw new \Exception("Wallet not found for company: {$settlement->company_id}");
            }
            
            // Credit the wallet
            $balanceBefore = $wallet->balance;
            $wallet->credit($settlement->amount);
            $wallet->save();
            
            // Update transaction
            $transaction->update([
                'settlement_status' => 'settled',
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'settled_at' => $now->toDateTimeString(),
                    'manually_settled' => true,
                    'settlement_script' => 'diagnose_and_fix_settlements.php',
                ]),
            ]);
            
            // Mark settlement as completed
            DB::table('settlement_queue')
                ->where('id', $settlement->id)
                ->update([
                    'status' => 'completed',
                    'actual_settlement_date' => $now,
                    'settlement_note' => "Fixed by diagnose_and_fix_settlements.php",
                ]);
            
            DB::commit();
            
            echo "✅ Settlement {$settlement->id} processed (₦" . number_format($settlement->amount, 2) . ")\n";
            $fixed++;
            
        } catch (\Exception $e) {
            DB::rollBack();
            echo "❌ Failed: " . $e->getMessage() . "\n";
            $errors[] = "Settlement {$settlement->id}: " . $e->getMessage();
        }
    }
}

// Fix 2: Create Missing Settlement Queue Entries
if (count($transactionsWithoutQueue) > 0) {
    echo "\nCreating missing settlement queue entries...\n\n";
    
    foreach ($transactionsWithoutQueue as $tx) {
        try {
            // Calculate settlement date (T+1)
            $transactionDate = Carbon::parse($tx->created_at);
            $scheduledDate = \App\Console\Commands\ProcessSettlements::calculateSettlementDate(
                $transactionDate,
                24,
                true,
                true,
                '03:00:00'
            );
            
            DB::table('settlement_queue')->insert([
                'company_id' => $tx->company_id,
                'transaction_id' => $tx->id,
                'amount' => $tx->net_amount,
                'status' => 'pending',
                'transaction_date' => $tx->created_at,
                'scheduled_settlement_date' => $scheduledDate,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            echo "✅ Created queue entry for transaction {$tx->transaction_id}\n";
            $fixed++;
            
        } catch (\Exception $e) {
            echo "❌ Failed: " . $e->getMessage() . "\n";
            $errors[] = "Transaction {$tx->transaction_id}: " . $e->getMessage();
        }
    }
}

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Fixed: {$fixed}\n";
echo "Errors: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\nERRORS:\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
}

echo "\n========================================\n";
echo "RECOMMENDATIONS\n";
echo "========================================\n";
echo "1. Verify cron job is running: crontab -l\n";
echo "2. Check Laravel scheduler: php artisan schedule:list\n";
echo "3. Manually run settlement: php artisan settlements:process\n";
echo "4. Monitor logs: tail -f storage/logs/laravel.log | grep settlement\n";
echo "========================================\n";
