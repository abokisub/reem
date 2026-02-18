<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

echo "=== TESTING ADMIN API DIRECTLY ===\n\n";

// Get admin user token
$admin = DB::table('users')
    ->where('email', 'admin@pointwave.com')
    ->first();

if (!$admin) {
    echo "ERROR: Admin user not found!\n";
    exit(1);
}

echo "Admin User Found:\n";
echo "  ID: {$admin->id}\n";
echo "  Email: {$admin->email}\n";
echo "  Type: {$admin->type}\n\n";

// Simulate the UserSystem method
echo "Simulating UserSystem API call...\n\n";

// Check if user is admin
$check_user = DB::table('users')
    ->where(['status' => 'active', 'id' => $admin->id])
    ->where(function ($query) {
        $query->whereIn('type', ['admin', 'ADMIN']);
    });

echo "Admin check count: " . $check_user->count() . "\n\n";

if ($check_user->count() > 0) {
    // Calculate metrics
    $total_revenue = DB::table('company_wallets')->sum('balance');
    $total_transactions = DB::table('transactions')->count();
    $successful_transactions = DB::table('transactions')->where('status', 'success')->count();
    $failed_transactions = DB::table('transactions')->where('status', 'failed')->count();
    
    $pending_settlement = 0;
    if (Schema::hasTable('settlement_queue')) {
        $pending_settlement = DB::table('settlement_queue')->where('status', 'pending')->count();
    }
    
    $active_businesses = DB::table('companies')->where('status', 'active')->count();
    $registered_businesses = DB::table('companies')->count();
    $pending_activations = DB::table('companies')->where('status', 'pending')->count();
    $total_virtual_accounts = DB::table('virtual_accounts')->count();

    $users_info = [
        'total_revenue' => $total_revenue,
        'total_transactions' => $total_transactions,
        'successful_transactions' => $successful_transactions,
        'failed_transactions' => $failed_transactions,
        'pending_settlement' => $pending_settlement,
        'active_businesses' => $active_businesses,
        'registered_businesses' => $registered_businesses,
        'pending_activations' => $pending_activations,
        'total_virtual_accounts' => $total_virtual_accounts,
        'all_user' => DB::table('users')->count(),
    ];

    echo "API Response Data:\n";
    echo json_encode($users_info, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "✓ Total Revenue: ₦" . number_format($total_revenue, 2) . "\n";
    echo "✓ Total Transactions: {$total_transactions}\n";
    echo "✓ Active Businesses: {$active_businesses}\n";
    echo "✓ Total Users: " . $users_info['all_user'] . "\n";
} else {
    echo "ERROR: Admin authorization failed!\n";
}

echo "\n=== DONE ===\n";
