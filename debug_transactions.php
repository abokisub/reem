<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DEBUGGING TRANSACTIONS ===\n\n";

// Get user
$user = DB::table('users')->where('email', 'abokisub@gmail.com')->first();
echo "User ID: {$user->id}\n";
echo "Username: {$user->username}\n";
echo "Active Company ID: {$user->active_company_id}\n\n";

// Check all transactions in the table
echo "1. ALL TRANSACTIONS IN DATABASE:\n";
$allTransactions = DB::table('transactions')->get();
echo "   Total Count: " . $allTransactions->count() . "\n\n";

if ($allTransactions->count() > 0) {
    echo "   Sample transactions:\n";
    foreach ($allTransactions->take(5) as $tx) {
        echo "   - ID: {$tx->id}\n";
        echo "     Transaction ID: {$tx->transaction_id}\n";
        echo "     Company ID: {$tx->company_id}\n";
        echo "     Type: {$tx->type}\n";
        echo "     Channel: {$tx->channel}\n";
        echo "     Amount: ₦{$tx->amount}\n";
        echo "     Status: {$tx->status}\n";
        echo "     Created: {$tx->created_at}\n\n";
    }
}

// Check transactions for this company
echo "2. TRANSACTIONS FOR COMPANY {$user->active_company_id}:\n";
$companyTransactions = DB::table('transactions')
    ->where('company_id', $user->active_company_id)
    ->get();
echo "   Count: " . $companyTransactions->count() . "\n\n";

// Check transactions with type=credit
echo "3. CREDIT TRANSACTIONS FOR COMPANY {$user->active_company_id}:\n";
$creditTransactions = DB::table('transactions')
    ->where('company_id', $user->active_company_id)
    ->where('type', 'credit')
    ->get();
echo "   Count: " . $creditTransactions->count() . "\n\n";

// Check transactions with channel=virtual_account
echo "4. VIRTUAL ACCOUNT TRANSACTIONS FOR COMPANY {$user->active_company_id}:\n";
$vaTransactions = DB::table('transactions')
    ->where('company_id', $user->active_company_id)
    ->where('channel', 'virtual_account')
    ->get();
echo "   Count: " . $vaTransactions->count() . "\n\n";

// Check with all filters
echo "5. WITH ALL FILTERS (company_id + type=credit + channel=virtual_account):\n";
$filteredTransactions = DB::table('transactions')
    ->where('company_id', $user->active_company_id)
    ->where('type', 'credit')
    ->where('channel', 'virtual_account')
    ->get();
echo "   Count: " . $filteredTransactions->count() . "\n\n";

// Check what channels exist
echo "6. UNIQUE CHANNELS IN TRANSACTIONS TABLE:\n";
$channels = DB::table('transactions')
    ->select('channel')
    ->distinct()
    ->get();
foreach ($channels as $ch) {
    $count = DB::table('transactions')->where('channel', $ch->channel)->count();
    echo "   - {$ch->channel}: {$count} transactions\n";
}
echo "\n";

// Check what types exist
echo "7. UNIQUE TYPES IN TRANSACTIONS TABLE:\n";
$types = DB::table('transactions')
    ->select('type')
    ->distinct()
    ->get();
foreach ($types as $t) {
    $count = DB::table('transactions')->where('type', $t->type)->count();
    echo "   - {$t->type}: {$count} transactions\n";
}
echo "\n";

// Check company_wallet
echo "8. COMPANY WALLET:\n";
$wallet = DB::table('company_wallets')->where('company_id', $user->active_company_id)->first();
if ($wallet) {
    echo "   Balance: ₦{$wallet->balance}\n";
    echo "   Ledger Balance: ₦{$wallet->ledger_balance}\n";
    echo "   Pending Balance: ₦{$wallet->pending_balance}\n";
} else {
    echo "   No wallet found!\n";
}
echo "\n";

echo "=== END DEBUG ===\n";
