<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING VIRTUAL ACCOUNT DATA FOR DEPOSIT ===\n\n";

$txn = DB::table('transactions')
    ->where('transaction_id', 'txn_699861639a0ca24142')
    ->first();

if (!$txn) {
    echo "Transaction not found!\n";
    exit;
}

echo "Transaction ID: {$txn->transaction_id}\n";
echo "Company ID: {$txn->company_id}\n";
echo "Type: {$txn->type}\n";
echo "Transaction Type: {$txn->transaction_type}\n\n";

// Check metadata for virtual account info
$metadata = json_decode($txn->metadata, true);
if ($metadata) {
    echo "=== METADATA ===\n";
    if (isset($metadata['virtual_account_name'])) {
        echo "Virtual Account Name: " . $metadata['virtual_account_name'] . "\n";
    }
    if (isset($metadata['virtual_account_number'])) {
        echo "Virtual Account Number: " . $metadata['virtual_account_number'] . "\n";
    }
    echo "\n";
}

// Check if there's a virtual account record
echo "=== CHECKING VIRTUAL ACCOUNTS TABLE ===\n";
$va = DB::table('virtual_accounts')
    ->where('company_id', $txn->company_id)
    ->first();

if ($va) {
    echo "✅ Virtual Account Found:\n";
    echo "Account Number: " . ($va->account_number ?? 'NULL') . "\n";
    echo "Account Name: " . ($va->account_name ?? 'NULL') . "\n";
    echo "Bank Name: " . ($va->bank_name ?? 'NULL') . "\n";
    echo "Bank Code: " . ($va->bank_code ?? 'NULL') . "\n";
} else {
    echo "❌ No virtual account found for this company\n";
}

echo "\n=== DONE ===\n";
