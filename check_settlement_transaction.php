<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking Settlement Transaction Details\n";
echo "========================================\n\n";

// Get a settlement transaction
$transaction = DB::table('transactions')
    ->where('transaction_type', 'settlement')
    ->orderBy('id', 'desc')
    ->first();

if ($transaction) {
    echo "Transaction ID: {$transaction->id}\n";
    echo "Reference: {$transaction->ref}\n";
    echo "Type: {$transaction->type}\n";
    echo "Transaction Type: {$transaction->transaction_type}\n";
    echo "Status: {$transaction->status}\n";
    echo "Settlement Status: {$transaction->settlement_status}\n";
    echo "Amount: {$transaction->amount}\n";
    echo "Company ID: {$transaction->company_id}\n\n";
    
    // Check company settlement account
    $company = DB::table('companies')->where('id', $transaction->company_id)->first();
    if ($company) {
        echo "Company Settlement Account:\n";
        echo "  Bank Name: {$company->settlement_bank_name}\n";
        echo "  Account Number: {$company->settlement_account_number}\n";
        echo "  Account Name: {$company->settlement_account_name}\n\n";
    }
    
    // Check metadata
    if ($transaction->metadata) {
        $metadata = json_decode($transaction->metadata, true);
        echo "Metadata:\n";
        print_r($metadata);
        echo "\n";
    }
    
    // Check what the API returns
    echo "\nWhat API should return:\n";
    echo "  Sender Name: " . ($company->company_name ?? 'PointWave Business') . "\n";
    echo "  Sender Account: " . ($company->settlement_account_number ?? 'N/A') . "\n";
    echo "  Sender Bank: " . ($company->settlement_bank_name ?? 'N/A') . "\n";
    echo "  Settlement Status: " . ($transaction->settlement_status ?? 'unsettled') . "\n";
    
} else {
    echo "No settlement transactions found\n";
}
