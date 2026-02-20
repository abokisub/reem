<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== SETTLEMENT STATUS INVESTIGATION ===\n\n";

// Check all settlements in queue
$allSettlements = DB::table('settlement_queue')
    ->join('companies', 'settlement_queue.company_id', '=', 'companies.id')
    ->select(
        'settlement_queue.*',
        'companies.name as company_name'
    )
    ->orderBy('settlement_queue.created_at', 'desc')
    ->get();

echo "Total settlements in queue: " . $allSettlements->count() . "\n\n";

foreach (['pending', 'processing', 'completed', 'failed'] as $status) {
    $count = $allSettlements->where('status', $status)->count();
    $total = $allSettlements->where('status', $status)->sum('amount');
    echo "{$status}: {$count} (₦" . number_format($total, 2) . ")\n";
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Check recent successful deposits
$recentDeposits = DB::table('transactions')
    ->where('type', 'credit')
    ->where('category', 'deposit')
    ->where('status', 'success')
    ->where('created_at', '>=', Carbon::now()->subDays(2))
    ->select('id', 'transaction_id', 'amount', 'company_id', 'created_at')
    ->orderBy('created_at', 'desc')
    ->get();

echo "Recent successful deposits (last 2 days): " . $recentDeposits->count() . "\n\n";

// Check which deposits are NOT in settlement queue
foreach ($recentDeposits as $deposit) {
    $inQueue = DB::table('settlement_queue')
        ->where('transaction_id', $deposit->id)
        ->first();
    
    if (!$inQueue) {
        echo "⚠️  Deposit NOT in settlement queue:\n";
        echo "   Transaction ID: {$deposit->transaction_id}\n";
        echo "   Amount: ₦" . number_format($deposit->amount, 2) . "\n";
        echo "   Created: {$deposit->created_at}\n";
        echo "   Company ID: {$deposit->company_id}\n\n";
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Check company wallet balances
$wallets = DB::table('company_wallets')
    ->join('companies', 'company_wallets.company_id', '=', 'companies.id')
    ->select(
        'company_wallets.*',
        'companies.name as company_name'
    )
    ->get();

echo "COMPANY WALLET BALANCES:\n\n";
foreach ($wallets as $wallet) {
    echo "Company: {$wallet->company_name}\n";
    echo "Balance: ₦" . number_format($wallet->balance, 2) . "\n";
    echo "Ledger Balance: ₦" . number_format($wallet->ledger_balance, 2) . "\n";
    
    // Calculate pending settlement for this company
    $pendingAmount = DB::table('settlement_queue')
        ->where('company_id', $wallet->company_id)
        ->where('status', 'pending')
        ->sum('amount');
    
    echo "Pending Settlement: ₦" . number_format($pendingAmount, 2) . "\n";
    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Check settlement settings
$settings = DB::table('settings')->first();
if ($settings) {
    echo "SETTLEMENT CONFIGURATION:\n";
    echo "Auto Settlement Enabled: " . ($settings->auto_settlement_enabled ? 'YES' : 'NO') . "\n";
    echo "Settlement Delay: {$settings->settlement_delay_hours} hours\n";
    echo "Minimum Amount: ₦" . number_format($settings->settlement_minimum_amount, 2) . "\n";
    echo "Skip Weekends: " . ($settings->settlement_skip_weekends ? 'YES' : 'NO') . "\n";
    echo "Settlement Time: {$settings->settlement_time}\n";
}

echo "\n";
