<?php
// Find the actual user who registered recently
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "=== FINDING RECENT REGISTRATIONS ===\n\n";

// Find users created today
$today = date('Y-m-d');
$recentUsers = User::whereDate('created_at', $today)
    ->orderBy('created_at', 'desc')
    ->get();

echo "Users registered today ({$today}):\n";
foreach ($recentUsers as $user) {
    echo "- ID: {$user->id}, Username: {$user->username}, Phone: {$user->phone}\n";
    echo "  Created: {$user->created_at}\n";
    echo "  PalmPay Account: " . ($user->palmpay_account_number ?? 'None') . "\n";
    echo "  PalmPay Name: " . ($user->palmpay_account_name ?? 'None') . "\n";
    echo "\n";
}

// Also check for username "Subman" specifically
echo "Looking for username 'Subman':\n";
$subman = User::where('username', 'Subman')->first();
if ($subman) {
    echo "- ID: {$subman->id}, Username: {$subman->username}, Phone: {$subman->phone}\n";
    echo "  Created: {$subman->created_at}\n";
    echo "  PalmPay Account: " . ($subman->palmpay_account_number ?? 'None') . "\n";
    echo "  PalmPay Name: " . ($subman->palmpay_account_name ?? 'None') . "\n";
} else {
    echo "No user found with username 'Subman'\n";
}

// Check for any users with the conflicting account number
echo "\nLooking for account number 6662822179:\n";
$conflictUsers = User::where('palmpay_account_number', '6662822179')->get();
foreach ($conflictUsers as $user) {
    echo "- ID: {$user->id}, Username: {$user->username}, Phone: {$user->phone}\n";
    echo "  PalmPay Name: " . ($user->palmpay_account_name ?? 'None') . "\n";
    echo "  Created: {$user->created_at}\n";
    echo "\n";
}

if ($conflictUsers->count() === 0) {
    echo "No users found with account number 6662822179\n";
}

echo "=== ANALYSIS COMPLETE ===\n";