<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING ADMIN DASHBOARD DATA ===\n\n";

// Check company wallets
echo "1. COMPANY WALLETS:\n";
$wallets = DB::table('company_wallets')->get();
foreach ($wallets as $wallet) {
    echo "   Company ID: {$wallet->company_id}, Balance: ₦{$wallet->balance}\n";
}
$total_revenue = DB::table('company_wallets')->sum('balance');
echo "   TOTAL REVENUE: ₦{$total_revenue}\n\n";

// Check transactions
echo "2. TRANSACTIONS:\n";
$total_trans = DB::table('transactions')->count();
$success_trans = DB::table('transactions')->where('status', 'success')->count();
$failed_trans = DB::table('transactions')->where('status', 'failed')->count();
echo "   Total: {$total_trans}\n";
echo "   Successful: {$success_trans}\n";
echo "   Failed: {$failed_trans}\n\n";

// Check companies
echo "3. COMPANIES:\n";
$active_biz = DB::table('companies')->where('status', 'active')->count();
$total_biz = DB::table('companies')->count();
$pending_biz = DB::table('companies')->where('status', 'pending')->count();
echo "   Active: {$active_biz}\n";
echo "   Total: {$total_biz}\n";
echo "   Pending: {$pending_biz}\n\n";

// Check virtual accounts
echo "4. VIRTUAL ACCOUNTS:\n";
$total_va = DB::table('virtual_accounts')->count();
echo "   Total: {$total_va}\n\n";

// Check settlement queue
echo "5. SETTLEMENT QUEUE:\n";
$pending_settlement = DB::table('settlement_queue')->where('status', 'pending')->count();
echo "   Pending: {$pending_settlement}\n\n";

echo "=== EXPECTED API RESPONSE ===\n";
echo json_encode([
    'total_revenue' => $total_revenue,
    'total_transactions' => $total_trans,
    'successful_transactions' => $success_trans,
    'failed_transactions' => $failed_trans,
    'pending_settlement' => $pending_settlement,
    'active_businesses' => $active_biz,
    'registered_businesses' => $total_biz,
    'pending_activations' => $pending_biz,
    'total_virtual_accounts' => $total_va,
], JSON_PRETTY_PRINT);

echo "\n\n=== DONE ===\n";
