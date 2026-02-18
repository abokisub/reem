<?php
// Check dashboard data issue

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DASHBOARD DATA CHECK ===\n\n";

// 1. Check user and company
$user = DB::table('users')->where('email', 'abokisub@gmail.com')->first();
if ($user) {
    echo "User Found:\n";
    echo "  ID: {$user->id}\n";
    echo "  Username: {$user->username}\n";
    echo "  Active Company ID: {$user->active_company_id}\n\n";
    
    // 2. Check company
    $company = DB::table('companies')->where('id', $user->active_company_id)->first();
    if ($company) {
        echo "Company Found:\n";
        echo "  ID: {$company->id}\n";
        echo "  Name: {$company->name}\n\n";
    }
    
    // 3. Check transactions for this company
    $transactions = DB::table('transactions')
        ->where('company_id', $user->active_company_id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    echo "Recent Transactions (company_id={$user->active_company_id}):\n";
    echo "  Total Count: " . DB::table('transactions')->where('company_id', $user->active_company_id)->count() . "\n";
    echo "  Credit Transactions: " . DB::table('transactions')->where('company_id', $user->active_company_id)->where('type', 'credit')->count() . "\n\n";
    
    if ($transactions->count() > 0) {
        echo "Latest 5 Transactions:\n";
        foreach ($transactions as $tx) {
            echo "  - {$tx->transaction_id}: ₦{$tx->amount} ({$tx->status}) - {$tx->created_at}\n";
        }
    } else {
        echo "  No transactions found!\n";
    }
    
    echo "\n";
    
    // 4. Check webhook logs
    $webhooks = DB::table('webhook_logs')
        ->where('company_id', $user->active_company_id)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    echo "Recent Webhook Logs (company_id={$user->active_company_id}):\n";
    echo "  Total Count: " . DB::table('webhook_logs')->where('company_id', $user->active_company_id)->count() . "\n\n";
    
    if ($webhooks->count() > 0) {
        echo "Latest 5 Webhooks:\n";
        foreach ($webhooks as $wh) {
            echo "  - ID {$wh->id}: {$wh->event} ({$wh->status}) - {$wh->created_at}\n";
        }
    } else {
        echo "  No webhook logs found!\n";
    }
    
} else {
    echo "User not found!\n";
}

echo "\n=== END CHECK ===\n";
