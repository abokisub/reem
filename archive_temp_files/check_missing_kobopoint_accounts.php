<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use Illuminate\Support\Facades\DB;

echo "=== Checking Missing Kobopoint Virtual Accounts ===\n\n";

// Find Kobopoint
$kobopoint = Company::where('name', 'LIKE', '%kobopoint%')->first();

if (!$kobopoint) {
    echo "❌ Kobopoint not found\n";
    exit(1);
}

echo "Company: {$kobopoint->name} (ID: {$kobopoint->id})\n\n";

// Get users without virtual accounts
$usersWithoutAccounts = DB::table('company_users')
    ->join('users', 'company_users.user_id', '=', 'users.id')
    ->where('company_users.company_id', $kobopoint->id)
    ->whereNotExists(function ($query) use ($kobopoint) {
        $query->select(DB::raw(1))
            ->from('virtual_accounts')
            ->whereColumn('virtual_accounts.company_user_id', 'company_users.id')
            ->where('virtual_accounts.company_id', $kobopoint->id)
            ->where('virtual_accounts.status', 'active')
            ->whereNull('virtual_accounts.deleted_at');
    })
    ->select('users.id', 'users.name', 'users.email', 'users.phone', 'company_users.id as company_user_id')
    ->get();

echo "Users WITHOUT Virtual Accounts: " . $usersWithoutAccounts->count() . "\n\n";

if ($usersWithoutAccounts->count() > 0) {
    echo "--- Missing Accounts ---\n";
    foreach ($usersWithoutAccounts as $user) {
        echo "• {$user->name}\n";
        echo "  Email: {$user->email}\n";
        echo "  Phone: {$user->phone}\n";
        echo "  User ID: {$user->id}\n\n";
    }
    
    echo "=== Action Required ===\n";
    echo "Click 'Regenerate Accounts' button in admin dashboard again\n";
    echo "OR run: php artisan company:assign-fresh-kyc {$kobopoint->id} --regenerate\n";
} else {
    echo "✓ All users have virtual accounts!\n";
}

// Show recent successful accounts
echo "\n--- Recently Created Accounts (Last 10) ---\n";
$recentAccounts = DB::table('virtual_accounts')
    ->where('company_id', $kobopoint->id)
    ->where('status', 'active')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get(['account_number', 'customer_name', 'customer_email', 'created_at']);

foreach ($recentAccounts as $account) {
    echo "• {$account->account_number} - {$account->customer_name} ({$account->customer_email})\n";
    echo "  Created: {$account->created_at}\n";
}

echo "\n";
