<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "========================================\n";
echo "Wallet Balance Investigation\n";
echo "========================================\n\n";

// Get company wallet for company ID 2
$wallet = \App\Models\CompanyWallet::where('company_id', 2)->first();

if ($wallet) {
    echo "Company ID: 2\n";
    echo "Balance: ₦" . number_format($wallet->balance, 2) . "\n";
    echo "Ledger Balance: ₦" . number_format($wallet->ledger_balance, 2) . "\n";
    echo "Pending Balance: ₦" . number_format($wallet->pending_balance, 2) . "\n";
    echo "Raw Balance Value: " . $wallet->balance . "\n";
    echo "Balance Type: " . gettype($wallet->balance) . "\n\n";
    
    // Check recent transactions
    echo "Recent Transactions:\n";
    echo "-----------------------------------\n";
    $transactions = \App\Models\Transaction::where('company_id', 2)
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    foreach ($transactions as $txn) {
        echo "Reference: {$txn->reference}\n";
        echo "Type: {$txn->type} | Category: {$txn->category}\n";
        echo "Amount: ₦" . number_format($txn->amount, 2) . "\n";
        echo "Fee: ₦" . number_format($txn->fee, 2) . "\n";
        echo "Status: {$txn->status}\n";
        echo "Balance Before: ₦" . number_format($txn->balance_before, 2) . "\n";
        echo "Balance After: ₦" . number_format($txn->balance_after, 2) . "\n";
        echo "Created: {$txn->created_at}\n";
        echo "-----------------------------------\n";
    }
    
    // Check for any pending transfer_out transactions
    echo "\nPending Transfer Out Transactions:\n";
    echo "-----------------------------------\n";
    $pending = \App\Models\Transaction::where('company_id', 2)
        ->where('category', 'transfer_out')
        ->where('status', 'pending')
        ->get();
    
    if ($pending->count() > 0) {
        foreach ($pending as $txn) {
            echo "Reference: {$txn->reference}\n";
            echo "Amount: ₦" . number_format($txn->amount, 2) . "\n";
            echo "Fee: ₦" . number_format($txn->fee, 2) . "\n";
            echo "Total: ₦" . number_format($txn->total_amount, 2) . "\n";
            echo "Created: {$txn->created_at}\n";
            echo "-----------------------------------\n";
        }
    } else {
        echo "No pending transfer_out transactions\n";
    }
    
} else {
    echo "❌ Wallet not found for company ID 2\n";
}

echo "\n========================================\n";
echo "Investigation Complete\n";
echo "========================================\n";
