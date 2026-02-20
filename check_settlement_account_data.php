<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Models\Company;

echo "Checking Settlement Account Data\n";
echo "=================================\n\n";

// Get the settlement withdrawal transaction
$transaction = Transaction::where('transaction_type', 'settlement_withdrawal')
    ->orderBy('id', 'desc')
    ->first();

if (!$transaction) {
    echo "❌ No settlement_withdrawal transaction found\n";
    exit;
}

echo "Transaction ID: {$transaction->id}\n";
echo "Reference: {$transaction->transid}\n";
echo "Company ID: {$transaction->company_id}\n\n";

// Get company details
$company = $transaction->company;

echo "Company Details:\n";
echo "----------------\n";
echo "Name: {$company->name}\n";
echo "Company Name: " . ($company->company_name ?? 'NULL') . "\n";
echo "Account Number: " . ($company->account_number ?? 'NULL') . "\n";
echo "Bank Name: " . ($company->bank_name ?? 'NULL') . "\n";
echo "Settlement Account Number: " . ($company->settlement_account_number ?? 'NULL') . "\n";
echo "Settlement Bank Name: " . ($company->settlement_bank_name ?? 'NULL') . "\n\n";

// Check if company has virtual accounts
$virtualAccount = $company->virtualAccounts()->first();

if ($virtualAccount) {
    echo "Virtual Account Details:\n";
    echo "------------------------\n";
    echo "Account Number: " . ($virtualAccount->palmpay_account_number ?? $virtualAccount->account_number ?? 'NULL') . "\n";
    echo "Account Name: " . ($virtualAccount->palmpay_account_name ?? $virtualAccount->account_name ?? 'NULL') . "\n";
    echo "Bank Name: " . ($virtualAccount->palmpay_bank_name ?? $virtualAccount->bank_name ?? 'NULL') . "\n\n";
}

// Check transaction metadata
$metadata = json_decode($transaction->metadata, true) ?? [];
echo "Transaction Metadata:\n";
echo "---------------------\n";
print_r($metadata);

echo "\n✅ Done!\n";
