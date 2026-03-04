<?php
// Force fresh PointWave account creation for specific user
// This bypasses any cached/conflicting account assignments

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$phone = $argv[1] ?? '07040540018';
$confirm = $argv[2] ?? null;

if ($confirm !== 'CONFIRM') {
    echo "⚠️  WARNING: This will force fresh PointWave account creation\n";
    echo "This will clear existing PointWave data and trigger new account creation\n\n";
    echo "To proceed, run: php force_fresh_pointwave_account.php $phone CONFIRM\n";
    exit(1);
}

echo "=== FORCING FRESH POINTWAVE ACCOUNT CREATION ===\n";
echo "Phone: $phone\n\n";

// Find the user
$user = User::where('phone', $phone)->first();

if (!$user) {
    echo "❌ No user found with phone $phone\n";
    exit(1);
}

echo "Found user: {$user->username} (ID: {$user->id})\n";
echo "Current PointWave data:\n";
echo "- Account Number: " . ($user->pointwave_account_number ?? 'None') . "\n";
echo "- Account Name: " . ($user->pointwave_account_name ?? 'None') . "\n";
echo "- Customer ID: " . ($user->pointwave_customer_id ?? 'None') . "\n\n";

// Clear all PointWave data
echo "Clearing existing PointWave data...\n";
$user->update([
    'pointwave_account_number' => null,
    'pointwave_account_name' => null,
    'pointwave_bank_name' => null,
    'pointwave_customer_id' => null,
]);

echo "✅ PointWave data cleared\n\n";

// Trigger fresh account creation by calling the setup job
echo "Triggering fresh account creation...\n";

try {
    // Dispatch the job to create fresh accounts
    \App\Jobs\SetupUserVirtualAccounts::dispatch($user->id);
    echo "✅ Account creation job dispatched\n";
    echo "Fresh PointWave account will be created within 1-2 minutes\n";
} catch (\Exception $e) {
    echo "❌ Failed to dispatch job: " . $e->getMessage() . "\n";
    echo "Manual account creation will happen on next user login/transaction\n";
}

echo "\n📋 SUMMARY:\n";
echo "User: {$user->username}\n";
echo "Phone: {$user->phone}\n";
echo "Status: PointWave data cleared, fresh account creation initiated\n";
echo "\nThe user should now get a completely new PointWave account with correct name.\n";