#!/usr/bin/env php
<?php

/**
 * Settlement System Test Script
 * 
 * Run this on cPanel terminal to test settlement configuration
 * Usage: php test_settlement_system.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\CompanyWallet;
use App\Models\Transaction;
use App\Models\VirtualAccount;

echo "========================================\n";
echo "Settlement System Test\n";
echo "========================================\n\n";

// Test 1: Check Current Settings
echo "TEST 1: Current Settlement Settings\n";
echo "-----------------------------------\n";
$settings = DB::table('settings')->first();

if ($settings) {
    echo "✓ Settings found\n";
    echo "  Auto Settlement: " . ($settings->auto_settlement_enabled ? "ENABLED" : "DISABLED") . "\n";
    echo "  Delay Hours: " . $settings->settlement_delay_hours . " hours\n";
    echo "  Skip Weekends: " . ($settings->settlement_skip_weekends ? "YES" : "NO") . "\n";
    echo "  Skip Holidays: " . ($settings->settlement_skip_holidays ? "YES" : "NO") . "\n";
    echo "  Settlement Time: " . $settings->settlement_time . "\n";
    echo "  Minimum Amount: ₦" . number_format($settings->settlement_minimum_amount, 2) . "\n";
} else {
    echo "✗ No settings found!\n";
}

echo "\n";

// Test 2: Check Pending Settlements
echo "TEST 2: Pending Settlements\n";
echo "-----------------------------------\n";
$pending = DB::table('settlement_queue')
    ->where('status', 'pending')
    ->orderBy('scheduled_settlement_date')
    ->get();

echo "Total Pending: " . $pending->count() . "\n";

if ($pending->count() > 0) {
    echo "\nDetails:\n";
    foreach ($pending->take(5) as $s) {
        echo "  - Company ID: {$s->company_id}\n";
        echo "    Amount: ₦" . number_format($s->amount, 2) . "\n";
        echo "    Scheduled: {$s->scheduled_settlement_date}\n";
        echo "    Transaction Date: {$s->transaction_date}\n";
        echo "\n";
    }
    
    if ($pending->count() > 5) {
        echo "  ... and " . ($pending->count() - 5) . " more\n";
    }
}

echo "\n";

// Test 3: Check Company Wallet
echo "TEST 3: Company Wallet (ID: 2)\n";
echo "-----------------------------------\n";
$wallet = CompanyWallet::where('company_id', 2)->first();

if ($wallet) {
    echo "✓ Wallet found\n";
    echo "  Balance: ₦" . number_format($wallet->balance, 2) . "\n";
    echo "  Pending: ₦" . number_format($wallet->pending_balance, 2) . "\n";
    echo "  Total: ₦" . number_format($wallet->balance + $wallet->pending_balance, 2) . "\n";
} else {
    echo "✗ Wallet not found!\n";
}

echo "\n";

// Test 4: Recent Transactions
echo "TEST 4: Recent Transactions (Company ID: 2)\n";
echo "-----------------------------------\n";
$transactions = Transaction::where('company_id', 2)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "Total Recent: " . $transactions->count() . "\n\n";

foreach ($transactions as $t) {
    echo "  Transaction: {$t->transaction_id}\n";
    echo "  Amount: ₦" . number_format($t->amount, 2) . "\n";
    echo "  Fee: ₦" . number_format($t->fee, 2) . "\n";
    echo "  Net: ₦" . number_format($t->net_amount, 2) . "\n";
    echo "  Status: {$t->status}\n";
    echo "  Category: {$t->category}\n";
    
    // Check if it's company self-funding
    if ($t->virtual_account_id) {
        $va = VirtualAccount::find($t->virtual_account_id);
        if ($va) {
            $isSelfFunding = ($va->company_user_id === null);
            echo "  Type: " . ($isSelfFunding ? "COMPANY SELF-FUNDING" : "CLIENT PAYMENT") . "\n";
        }
    }
    
    // Check settlement status
    $metadata = $t->metadata ?? [];
    if (isset($metadata['settlement_status'])) {
        echo "  Settlement: {$metadata['settlement_status']}\n";
        if (isset($metadata['scheduled_settlement_date'])) {
            echo "  Scheduled: {$metadata['scheduled_settlement_date']}\n";
        }
    }
    
    echo "  Created: {$t->created_at}\n";
    echo "\n";
}

echo "\n";

// Test 5: Virtual Accounts
echo "TEST 5: Virtual Accounts (Company ID: 2)\n";
echo "-----------------------------------\n";
$virtualAccounts = VirtualAccount::where('company_id', 2)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo "Total: " . $virtualAccounts->count() . "\n\n";

foreach ($virtualAccounts as $va) {
    $isMaster = ($va->company_user_id === null);
    echo "  Account: {$va->palmpay_account_number}\n";
    echo "  Name: {$va->palmpay_account_name}\n";
    echo "  Type: " . ($isMaster ? "MASTER (Company)" : "CLIENT (User ID: {$va->company_user_id})") . "\n";
    echo "  Status: {$va->status}\n";
    echo "\n";
}

echo "\n";

// Test 6: Settlement Queue Statistics
echo "TEST 6: Settlement Queue Statistics\n";
echo "-----------------------------------\n";
$stats = DB::table('settlement_queue')
    ->select(
        DB::raw('status'),
        DB::raw('COUNT(*) as count'),
        DB::raw('SUM(amount) as total_amount')
    )
    ->groupBy('status')
    ->get();

foreach ($stats as $stat) {
    echo "  {$stat->status}: {$stat->count} items, ₦" . number_format($stat->total_amount, 2) . "\n";
}

echo "\n";

// Test 7: Check if cron job is set up
echo "TEST 7: Settlement Processing Command\n";
echo "-----------------------------------\n";
echo "To process settlements manually, run:\n";
echo "  php artisan settlements:process\n\n";

echo "To set up automatic processing (cron job):\n";
echo "  * * * * * cd /home/aboksdfs/app.pointwave.ng && php artisan settlements:process >> /dev/null 2>&1\n";

echo "\n";
echo "========================================\n";
echo "Test Complete\n";
echo "========================================\n";
