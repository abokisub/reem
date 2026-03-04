<?php
// Investigate PointWave API returning wrong accounts
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== POINTWAVE API INVESTIGATION ===\n\n";

// Check if we're looking at the right database
echo "1. DATABASE ANALYSIS:\n";
echo "Current database: " . config('database.default') . "\n";
echo "Database name: " . config('database.connections.' . config('database.default') . '.database') . "\n\n";

// Find all users with PalmPay accounts
echo "2. ALL USERS WITH PALMPAY ACCOUNTS:\n";
$usersWithAccounts = User::whereNotNull('palmpay_account_number')->get();
echo "Found " . $usersWithAccounts->count() . " users with PalmPay accounts:\n";

foreach ($usersWithAccounts as $user) {
    echo "- ID: {$user->id}, Username: {$user->username}, Phone: {$user->phone}\n";
    echo "  Account: {$user->palmpay_account_number}\n";
    echo "  Name: " . ($user->palmpay_account_name ?? 'None') . "\n";
    echo "  Created: {$user->created_at}\n";
    echo "\n";
}

// Check for the specific problematic account
echo "3. SEARCHING FOR ACCOUNT 6662822179:\n";
$problematicAccount = User::where('palmpay_account_number', '6662822179')->first();
if ($problematicAccount) {
    echo "✅ Found in our database:\n";
    echo "- User: {$problematicAccount->username}\n";
    echo "- Phone: {$problematicAccount->phone}\n";
    echo "- Account Name: {$problematicAccount->palmpay_account_name}\n";
} else {
    echo "❌ Account 6662822179 NOT found in our database\n";
    echo "This confirms the account exists on PalmPay but not in our system\n";
}

// Check recent activity
echo "\n4. RECENT USER ACTIVITY:\n";
$recentUsers = User::where('created_at', '>=', now()->subDays(1))
    ->orderBy('created_at', 'desc')
    ->get();

echo "Users created in last 24 hours:\n";
foreach ($recentUsers as $user) {
    echo "- ID: {$user->id}, Username: {$user->username}, Phone: {$user->phone}\n";
    echo "  Created: {$user->created_at}\n";
    echo "  PalmPay Account: " . ($user->palmpay_account_number ?? 'None') . "\n";
    echo "\n";
}

echo "=== CONCLUSION ===\n";
echo "If account 6662822179 is not in our database but exists on PalmPay,\n";
echo "this confirms that PointWave API is returning existing accounts\n";
echo "instead of creating fresh ones for new registrations.\n\n";

echo "NEXT STEPS:\n";
echo "1. Contact PointWave support about API returning existing accounts\n";
echo "2. Request they fix their account creation logic\n";
echo "3. Ask them to ensure unique account numbers for each request\n";