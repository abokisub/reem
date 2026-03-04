<?php
// Force fresh PalmPay account creation for specific user
// This bypasses any cached/conflicting account assignments

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$phone = $argv[1] ?? '07040540018';
$confirm = $argv[2] ?? null;

if ($confirm !== 'CONFIRM') {
    echo "⚠️  WARNING: This will force fresh PalmPay account creation\n";
    echo "This will clear existing PalmPay data and trigger new account creation\n\n";
    echo "To proceed, run: php force_fresh_pointwave_account.php $phone CONFIRM\n";
    exit(1);
}

echo "=== FORCING FRESH PALMPAY ACCOUNT CREATION ===\n";
echo "Phone: $phone\n\n";

// Find the user
$user = User::where('phone', $phone)->first();

if (!$user) {
    echo "❌ No user found with phone $phone\n";
    exit(1);
}

echo "Found user: {$user->username} (ID: {$user->id})\n";
echo "Current PalmPay data:\n";
echo "- Account Number: " . ($user->palmpay_account_number ?? 'None') . "\n";
echo "- Account Name: " . ($user->palmpay_account_name ?? 'None') . "\n";
echo "- Customer ID: " . ($user->palmpay_customer_id ?? 'None') . "\n\n";

// Clear all PalmPay data
echo "Clearing existing PalmPay data...\n";
$user->update([
    'palmpay_account_number' => null,
    'palmpay_account_name' => null,
    // Don't set palmpay_bank_name to null if it has a NOT NULL constraint
]);

echo "✅ PalmPay data cleared\n\n";

// The system will automatically create fresh accounts on next login/transaction
echo "📋 SUMMARY:\n";
echo "User: {$user->username}\n";
echo "Phone: {$user->phone}\n";
echo "Status: PalmPay data cleared\n";
echo "\nFresh account creation will happen automatically when:\n";
echo "1. User logs into the app\n";
echo "2. User makes a transaction\n";
echo "3. System detects missing PalmPay account\n";
echo "\nThe user should now get a completely new PalmPay account with correct name.\n";