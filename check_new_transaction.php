<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n========================================\n";
echo "CHECK NEW TRANSACTION\n";
echo "========================================\n\n";

// Get the latest transaction
$transaction = DB::table('transactions')
    ->where('reference', 'MI2024835345652285440')
    ->orWhere('transaction_ref', 'like', 'txn_69985e87d2ab461058%')
    ->first();

if ($transaction) {
    echo "✅ Transaction Found in Database!\n\n";
    echo "Transaction ID: {$transaction->id}\n";
    echo "Transaction Ref: {$transaction->transaction_ref}\n";
    echo "Reference: {$transaction->reference}\n";
    echo "Session ID: {$transaction->session_id}\n";
    echo "Type: {$transaction->type}\n";
    echo "Transaction Type: {$transaction->transaction_type}\n";
    echo "Amount: ₦{$transaction->amount}\n";
    echo "Fee: ₦{$transaction->fee}\n";
    echo "Net Amount: ₦{$transaction->net_amount}\n";
    echo "Status: {$transaction->status}\n";
    echo "Settlement Status: {$transaction->settlement_status}\n";
    echo "Company ID: {$transaction->company_id}\n";
    echo "Description: {$transaction->description}\n";
    echo "Created: {$transaction->created_at}\n";
    echo "\n";
    
    // Check company wallet balance
    $wallet = DB::table('company_wallets')
        ->where('company_id', $transaction->company_id)
        ->first();
    
    if ($wallet) {
        echo "Company Wallet Balance: ₦{$wallet->balance}\n";
    }
    
    echo "\n";
    echo "This transaction should appear in:\n";
    echo "- RA Transactions page (for company users)\n";
    echo "- Admin Statement page (for admin users)\n";
    echo "\n";
    echo "If not showing, try:\n";
    echo "1. Hard refresh browser (Ctrl+Shift+R)\n";
    echo "2. Clear Laravel caches on server\n";
    echo "3. Check browser console for errors\n";
    
} else {
    echo "❌ Transaction NOT found in database!\n";
    echo "\n";
    echo "This means the webhook was received but transaction creation failed.\n";
    echo "Check the full Laravel logs for errors.\n";
}

echo "\n";
