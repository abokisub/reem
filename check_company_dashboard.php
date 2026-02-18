<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== COMPANY DASHBOARD DATA CHECK ===\n\n";

// Get company user
$user = DB::table('users')->where('email', 'abokisub@gmail.com')->first();
echo "User: {$user->username}\n";
echo "Active Company ID: {$user->active_company_id}\n\n";

// Check company wallet
$wallet = DB::table('company_wallets')
    ->where('company_id', $user->active_company_id)
    ->where('currency', 'NGN')
    ->first();

if ($wallet) {
    echo "Company Wallet:\n";
    echo "  Balance: ₦{$wallet->balance}\n";
    echo "  Ledger Balance: ₦{$wallet->ledger_balance}\n\n";
} else {
    echo "❌ No wallet found!\n\n";
}

// Check transactions
$transactions = DB::table('transactions')
    ->where('company_id', $user->active_company_id)
    ->where('type', 'credit')
    ->where('status', 'success')
    ->get();

echo "Credit Transactions (company_id={$user->active_company_id}):\n";
echo "  Count: " . $transactions->count() . "\n";
echo "  Total Amount: ₦" . $transactions->sum('amount') . "\n\n";

// Check settlement queue
$settlements = DB::table('settlement_queue')
    ->where('company_id', $user->active_company_id)
    ->where('status', 'pending')
    ->get();

echo "Pending Settlements:\n";
echo "  Count: " . $settlements->count() . "\n";
echo "  Total Amount: ₦" . $settlements->sum('amount') . "\n\n";

// Check what UserDashboardController would return
echo "=== SIMULATING UserDashboardController ===\n\n";

$totalRevenue = DB::table('transactions')
    ->where('company_id', $user->active_company_id)
    ->where('type', 'credit')
    ->where('channel', 'virtual_account')
    ->where('status', 'success')
    ->sum('amount');

echo "Total Revenue (virtual_account credits): ₦{$totalRevenue}\n";

$totalTransactions = DB::table('message')
    ->where('username', $user->username)
    ->count();

echo "Total Transactions (message table): {$totalTransactions}\n";

$pendingSettlement = DB::table('transactions')
    ->where('company_id', $user->active_company_id)
    ->where('type', 'credit')
    ->where('status', 'pending')
    ->sum('amount');

echo "Pending Settlement: ₦{$pendingSettlement}\n";
