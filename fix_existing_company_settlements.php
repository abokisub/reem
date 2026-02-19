#!/usr/bin/env php
<?php

/**
 * Fix Existing Company Self-Funding Transactions
 * 
 * This script will:
 * 1. Find all transactions in settlement queue that are company self-funding
 * 2. Credit the wallet immediately
 * 3. Remove from settlement queue
 * 4. Update transaction metadata
 * 
 * Run this AFTER the migration and code deployment
 * Usage: php fix_existing_company_settlements.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\CompanyWallet;
use App\Models\Transaction;
use App\Models\VirtualAccount;

echo "========================================\n";
echo "Fix Existing Company Self-Funding\n";
echo "========================================\n\n";

// Find all pending settlements
$pendingSettlements = DB::table('settlement_queue')
    ->where('status', 'pending')
    ->get();

echo "Found {$pendingSettlements->count()} pending settlements\n\n";

$fixed = 0;
$skipped = 0;

foreach ($pendingSettlements as $settlement) {
    $transaction = Transaction::find($settlement->transaction_id);
    
    if (!$transaction) {
        echo "⚠️  Transaction {$settlement->transaction_id} not found, skipping\n";
        $skipped++;
        continue;
    }
    
    // Check if it's company self-funding
    if ($transaction->virtual_account_id) {
        $virtualAccount = VirtualAccount::find($transaction->virtual_account_id);
        
        if ($virtualAccount && $virtualAccount->company_user_id === null) {
            // This is company self-funding!
            echo "🔧 Fixing: {$transaction->transaction_id}\n";
            echo "   Company ID: {$transaction->company_id}\n";
            echo "   Amount: ₦" . number_format($settlement->amount, 2) . "\n";
            
            try {
                DB::beginTransaction();
                
                // Credit the wallet
                $wallet = CompanyWallet::where('company_id', $transaction->company_id)
                    ->where('currency', 'NGN')
                    ->lockForUpdate()
                    ->first();
                
                if ($wallet) {
                    $balanceBefore = $wallet->balance;
                    $wallet->credit($settlement->amount);
                    $wallet->save();
                    
                    // Update transaction
                    $transaction->update([
                        'balance_before' => $balanceBefore,
                        'balance_after' => $wallet->balance,
                        'metadata' => array_merge($transaction->metadata ?? [], [
                            'settlement_status' => 'instant',
                            'settlement_type' => 'company_self_funding',
                            'bypass_reason' => 'Master account funding - retroactively fixed',
                            'fixed_at' => now()->toDateTimeString(),
                        ]),
                    ]);
                    
                    // Remove from settlement queue
                    DB::table('settlement_queue')
                        ->where('id', $settlement->id)
                        ->update([
                            'status' => 'completed',
                            'actual_settlement_date' => now(),
                            'settlement_note' => 'Company self-funding - retroactively credited',
                        ]);
                    
                    DB::commit();
                    
                    echo "   ✅ Fixed! Balance: ₦" . number_format($wallet->balance, 2) . "\n\n";
                    $fixed++;
                } else {
                    echo "   ❌ Wallet not found\n\n";
                    DB::rollBack();
                    $skipped++;
                }
            } catch (\Exception $e) {
                DB::rollBack();
                echo "   ❌ Error: {$e->getMessage()}\n\n";
                $skipped++;
            }
        } else {
            // This is a client payment, leave it in queue
            $skipped++;
        }
    } else {
        // No virtual account, skip
        $skipped++;
    }
}

echo "========================================\n";
echo "Summary:\n";
echo "  Fixed: {$fixed}\n";
echo "  Skipped: {$skipped}\n";
echo "========================================\n";

if ($fixed > 0) {
    echo "\n✅ Company self-funding transactions have been credited!\n";
    echo "Check your wallet balance:\n";
    echo "  php artisan tinker --execute=\"\n";
    echo "    \\\$w = \\App\\Models\\CompanyWallet::where('company_id', 2)->first();\n";
    echo "    echo 'Balance: ₦' . number_format(\\\$w->balance, 2) . '\\n';\n";
    echo "  \"\n";
}
