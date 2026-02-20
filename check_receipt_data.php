<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING RECEIPT DATA FOR TRANSACTION ===\n\n";

$txn = DB::table('transactions')
    ->where('transaction_id', 'txn_699861639a0ca24142')
    ->first();

if (!$txn) {
    echo "Transaction not found!\n";
    exit;
}

echo "Transaction ID: {$txn->transaction_id}\n";
echo "Type: {$txn->type}\n";
echo "Transaction Type: {$txn->transaction_type}\n";
echo "Amount: ₦" . number_format($txn->amount, 2) . "\n\n";

echo "=== METADATA ===\n";
$metadata = json_decode($txn->metadata, true);
if ($metadata) {
    echo json_encode($metadata, JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "No metadata found\n\n";
}

echo "=== RECIPIENT FIELDS ===\n";
echo "recipient_account_name: " . ($txn->recipient_account_name ?? 'NULL') . "\n";
echo "recipient_account_number: " . ($txn->recipient_account_number ?? 'NULL') . "\n";
echo "recipient_bank_name: " . ($txn->recipient_bank_name ?? 'NULL') . "\n\n";

echo "=== COMPANY INFO ===\n";
$company = DB::table('companies')->where('id', $txn->company_id)->first();
if ($company) {
    echo "Company Name: " . ($company->company_name ?? $company->name ?? 'NULL') . "\n";
    echo "Username: " . ($company->username ?? 'NULL') . "\n";
    echo "Email: " . ($company->email ?? 'NULL') . "\n";
}
