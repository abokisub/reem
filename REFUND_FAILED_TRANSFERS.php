<?php

/**
 * Refund Failed Transfers Due to ENUM Error
 * 
 * This script identifies and refunds transactions that failed due to the
 * 'debited' status not being in the database ENUM.
 */

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "========================================\n";
echo "REFUNDING FAILED TRANSFERS\n";
echo "========================================\n\n";

// Find transactions that failed due to ENUM error in the last 24 hours
$failedTransactions = DB::table('transactions')
    ->where('created_at', '>=', now()->subHours(24))
    ->where('status', 'pending')
    ->where('category', 'transfer_out')
    ->get();

if ($failedTransactions->isEmpty()) {
    echo "No failed transactions found that need refunding.\n";
    exit(0);
}

echo "Found " . $failedTransactions->count() . " transactions to review:\n\n";

$refundedCount = 0;
$skippedCount = 0;

foreach ($failedTransactions as $txn) {
    echo "Transaction: {$txn->transaction_id}\n";
    echo "  Reference: {$txn->reference}\n";
    echo "  Company ID: {$txn->company_id}\n";
    echo "  Amount: ₦" . number_format($txn->total_amount, 2) . "\n";
    echo "  Status: {$txn->status}\n";
    
    // Check if company was actually debited
    $companyWallet = DB::table('company_wallets')
        ->where('company_id', $txn->company_id)
        ->first();
    
    if (!$companyWallet) {
        echo "  ⚠ Company wallet not found. Skipping.\n\n";
        $skippedCount++;
        continue;
    }
    
    // Check if there's a corresponding debit in the ledger
    $ledgerEntry = DB::table('ledger_entries')
        ->where('reference', $txn->reference)
        ->where('type', 'debit')
        ->exists();
    
    if (!$ledgerEntry) {
        echo "  ℹ No ledger debit found. Company was not charged. Marking as failed.\n";
        
        DB::table('transactions')
            ->where('id', $txn->id)
            ->update([
                'status' => 'failed',
                'error_message' => 'Transaction failed before debit - no refund needed',
                'updated_at' => now()
            ]);
        
        $skippedCount++;
        echo "  ✓ Marked as failed (no refund needed)\n\n";
        continue;
    }
    
    // Company was debited, need to refund
    echo "  💰 Company was debited. Processing refund...\n";
    
    try {
        DB::beginTransaction();
        
        // 1. Credit the company wallet
        DB::table('company_wallets')
            ->where('company_id', $txn->company_id)
            ->increment('balance', $txn->total_amount);
        
        DB::table('company_wallets')
            ->where('company_id', $txn->company_id)
            ->increment('ledger_balance', $txn->total_amount);
        
        // 2. Create reversal ledger entries
        $companyAccount = DB::table('ledger_accounts')
            ->where('account_type', 'company_wallet')
            ->where('company_id', $txn->company_id)
            ->first();
        
        $providerAccount = DB::table('ledger_accounts')
            ->where('account_type', 'bank_clearing')
            ->whereNull('company_id')
            ->first();
        
        $revenueAccount = DB::table('ledger_accounts')
            ->where('account_type', 'revenue')
            ->whereNull('company_id')
            ->first();
        
        if ($companyAccount && $providerAccount && $revenueAccount) {
            // Reverse the original entries
            DB::table('ledger_entries')->insert([
                [
                    'account_id' => $companyAccount->id,
                    'reference' => $txn->reference . '_REV',
                    'type' => 'credit',
                    'amount' => $txn->total_amount,
                    'description' => 'Refund: Failed transfer due to system error',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'account_id' => $providerAccount->id,
                    'reference' => $txn->reference . '_REV',
                    'type' => 'debit',
                    'amount' => $txn->amount,
                    'description' => 'Refund: Failed transfer due to system error',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'account_id' => $revenueAccount->id,
                    'reference' => $txn->reference . '_REV',
                    'type' => 'debit',
                    'amount' => $txn->fee,
                    'description' => 'Refund: Failed transfer due to system error',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);
        }
        
        // 3. Update transaction status
        DB::table('transactions')
            ->where('id', $txn->id)
            ->update([
                'status' => 'failed',
                'error_message' => 'System error - Refunded',
                'is_refunded' => true,
                'refunded_at' => now(),
                'refund_reason' => 'Database ENUM error - automatic refund',
                'updated_at' => now()
            ]);
        
        // 4. Update system wallets
        DB::table('system_wallets')
            ->where('slug', 'platform_revenue')
            ->decrement('balance', $txn->fee);
        
        DB::table('system_wallets')
            ->where('slug', 'bank_clearing')
            ->decrement('balance', $txn->amount);
        
        // 5. Update message table if exists
        DB::table('message')
            ->where('transid', $txn->reference)
            ->update([
                'plan_status' => 0,
                'message' => 'Transfer FAILED and REFUNDED due to system error. Amount: ₦' . number_format($txn->total_amount, 2),
                'newbal' => DB::raw('oldbal')
            ]);
        
        DB::commit();
        
        echo "  ✓ Refund processed successfully\n";
        echo "  ✓ Amount refunded: ₦" . number_format($txn->total_amount, 2) . "\n\n";
        
        $refundedCount++;
        
        // Log the refund
        Log::info('Auto-refund processed for failed transfer', [
            'transaction_id' => $txn->transaction_id,
            'reference' => $txn->reference,
            'company_id' => $txn->company_id,
            'amount' => $txn->total_amount,
            'reason' => 'Database ENUM error'
        ]);
        
    } catch (\Exception $e) {
        DB::rollBack();
        echo "  ✗ Refund failed: " . $e->getMessage() . "\n\n";
        $skippedCount++;
        
        Log::error('Failed to process auto-refund', [
            'transaction_id' => $txn->transaction_id,
            'error' => $e->getMessage()
        ]);
    }
}

echo "========================================\n";
echo "REFUND SUMMARY\n";
echo "========================================\n";
echo "Total transactions reviewed: " . $failedTransactions->count() . "\n";
echo "Successfully refunded: $refundedCount\n";
echo "Skipped/Failed: $skippedCount\n";
echo "\n";

if ($refundedCount > 0) {
    echo "✓ Refunds have been processed.\n";
    echo "  Please verify the company wallet balances.\n";
}

echo "\n";
