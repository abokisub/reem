<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking Actual Settlement Transactions\n";
echo "========================================\n\n";

// Check recent debit transactions (settlements are debits)
$transactions = DB::table('transactions')
    ->where('type', 'debit')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

echo "Found " . $transactions->count() . " debit transactions\n\n";

foreach ($transactions as $transaction) {
    echo "ID: {$transaction->id}\n";
    echo "Reference: {$transaction->reference}\n";
    echo "Type: {$transaction->type}\n";
    echo "Transaction Type: " . ($transaction->transaction_type ?? 'NULL') . "\n";
    echo "Status: {$transaction->status}\n";
    echo "Settlement Status: " . ($transaction->settlement_status ?? 'NULL') . "\n";
    echo "Amount: {$transaction->amount}\n";
    echo "Description: " . ($transaction->description ?? 'NULL') . "\n";
    echo "---\n\n";
}

// Check the specific transaction from the screenshot
$ref = 'TF_050d0b5960137';
$transaction = DB::table('transactions')->where('reference', $ref)->first();

if ($transaction) {
    echo "\nTransaction from screenshot ($ref):\n";
    echo "Type: {$transaction->type}\n";
    echo "Transaction Type: " . ($transaction->transaction_type ?? 'NULL') . "\n";
    echo "Status: {$transaction->status}\n";
    echo "Settlement Status: " . ($transaction->settlement_status ?? 'NULL') . "\n";
    echo "Company ID: {$transaction->company_id}\n";
    
    $company = DB::table('companies')->where('id', $transaction->company_id)->first();
    if ($company) {
        echo "\nCompany Info:\n";
        echo "Name: {$company->company_name}\n";
        echo "Settlement Bank: " . ($company->settlement_bank_name ?? 'NULL') . "\n";
        echo "Settlement Account: " . ($company->settlement_account_number ?? 'NULL') . "\n";
        echo "Settlement Account Name: " . ($company->settlement_account_name ?? 'NULL') . "\n";
    }
}
