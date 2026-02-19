<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking transaction recipient bank information...\n\n";

// Get the transaction with account number 7040540018
$transaction = DB::table('transactions')
    ->where('recipient_account_number', '7040540018')
    ->orderBy('id', 'desc')
    ->first();

if ($transaction) {
    echo "Transaction ID: {$transaction->id}\n";
    echo "Reference: {$transaction->reference}\n";
    echo "Recipient Account: {$transaction->recipient_account_number}\n";
    echo "Recipient Name: {$transaction->recipient_account_name}\n";
    echo "Recipient Bank Code: {$transaction->recipient_bank_code}\n";
    echo "Recipient Bank Name: " . ($transaction->recipient_bank_name ?? 'NULL') . "\n\n";
    
    // Check if we can find the bank name from banks table
    if ($transaction->recipient_bank_code) {
        $bank = DB::table('banks')->where('code', $transaction->recipient_bank_code)->first();
        if ($bank) {
            echo "Bank found in banks table:\n";
            echo "  Code: {$bank->code}\n";
            echo "  Name: {$bank->name}\n\n";
            
            // Update the transaction if bank_name is missing
            if (!$transaction->recipient_bank_name) {
                DB::table('transactions')
                    ->where('id', $transaction->id)
                    ->update(['recipient_bank_name' => $bank->name]);
                echo "✓ Updated transaction with bank name: {$bank->name}\n";
            }
        } else {
            echo "✗ Bank code {$transaction->recipient_bank_code} not found in banks table\n";
        }
    }
} else {
    echo "No transaction found with recipient account 7040540018\n";
}

echo "\nDone!\n";
