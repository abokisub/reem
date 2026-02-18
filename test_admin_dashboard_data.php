<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING ADMIN DASHBOARD DATA ===\n\n";

// Test 1: Check company wallets
echo "1. COMPANY WALLETS:\n";
$wallets = DB::table('company_wallets')->get();
foreach ($wallets as $wallet) {
    echo "   Company ID: {$wallet->company_id}, Balance: ₦{$wallet->balance}\n";
}
$total_revenue = DB::table('company_wallets')->sum('balance');
echo "   TOTAL REVENUE: ₦{$total_revenue}\n\n";

// Test 2: Check transactions
echo "2. TRANSACTIONS:\n";
$total_transactions = DB::table('transactions')->count();
$successful = DB::table('transactions')->where('status', 'success')->count();
$failed = DB::table('transactions')->where('status', 'failed')->count();
echo "   Total: {$total_transactions}\n";
echo "   Successful: {$successful}\n";
echo "   Failed: {$failed}\n\n";

// Test 3: Check companies
echo "3. COMPANIES:\n";
$active_businesses = DB::table('companies')->where('status', 'active')->count();
$total_companies = DB::table('companies')->count();
$pending = DB::table('companies')->where('status', 'pending')->count();
echo "   Active: {$active_businesses}\n";
echo "   Total: {$total_companies}\n";
echo "   Pending: {$pending}\n\n";

// Test 4: Check virtual accounts
echo "4. VIRTUAL ACCOUNTS:\n";
$total_va = DB::table('virtual_accounts')->count();
echo "   Total: {$total_va}\n\n";

// Test 5: Check settlement queue
echo "5. SETTLEMENT QUEUE:\n";
$pending_settlement = DB::table('settlement_queue')->where('status', 'pending')->count();
echo "   Pending: {$pending_settlement}\n\n";

// Test 6: Simulate the exact query from UserSystem method
echo "6. SIMULATING ADMIN DASHBOARD API RESPONSE:\n";
$users_info = [
    'total_revenue' => DB::table('company_wallets')->sum('balance'),
    'total_transactions' => DB::table('transactions')->count(),
    'successful_transactions' => DB::table('transactions')->where('status', 'success')->count(),
    'failed_transactions' => DB::table('transactions')->where('status', 'failed')->count(),
    'pending_settlement' => DB::table('settlement_queue')->where('status', 'pending')->count(),
    'active_businesses' => DB::table('companies')->where('status', 'active')->count(),
    'registered_businesses' => DB::table('companies')->count(),
    'pending_activations' => DB::table('companies')->where('status', 'pending')->count(),
    'total_virtual_accounts' => DB::table('virtual_accounts')->count(),
];

echo json_encode($users_info, JSON_PRETTY_PRINT) . "\n\n";

echo "=== DONE ===\n";
