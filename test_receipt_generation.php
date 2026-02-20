<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING RECEIPT GENERATION ===\n\n";

// Get the transaction
$transaction = App\Models\Transaction::where('transaction_id', 'txn_699861639a0ca24142')->first();

if (!$transaction) {
    echo "❌ Transaction not found!\n";
    exit;
}

echo "✅ Transaction found: {$transaction->transaction_id}\n";
echo "Type: {$transaction->type}\n";
echo "Transaction Type: {$transaction->transaction_type}\n\n";

// Simulate what ReceiptService does
$metadata = is_array($transaction->metadata) ? $transaction->metadata : json_decode($transaction->metadata, true) ?? [];

echo "=== METADATA CONTENT ===\n";
echo json_encode($metadata, JSON_PRETTY_PRINT) . "\n\n";

// Check if this is a credit transaction
$isCredit = $transaction->type === 'credit' || $transaction->transaction_type === 'va_deposit';
echo "Is Credit Transaction: " . ($isCredit ? 'YES' : 'NO') . "\n\n";

// Extract customer details based on transaction type
if ($isCredit) {
    echo "=== EXTRACTING SENDER INFO (CREDIT) ===\n";
    $customerName = $metadata['sender_name'] ?? $metadata['sender_account_name'] ?? '';
    $customerAccount = $metadata['sender_account'] ?? '';
    $customerBank = $metadata['sender_bank'] ?? $metadata['sender_bank_name'] ?? '';
} else {
    echo "=== EXTRACTING RECIPIENT INFO (DEBIT) ===\n";
    $customerName = $transaction->recipient_account_name ?? '';
    $customerAccount = $transaction->recipient_account_number ?? '';
    $customerBank = $transaction->recipient_bank_name ?? '';
}

echo "Customer Name: " . ($customerName ?: '-') . "\n";
echo "Customer Account: " . ($customerAccount ?: '-') . "\n";
echo "Customer Bank: " . ($customerBank ?: '-') . "\n\n";

// Get company info
$company = $transaction->company;
echo "=== COMPANY INFO ===\n";
echo "Company Name: " . ($company->company_name ?? $company->name ?? '-') . "\n";
echo "Company Email: " . ($company->email ?? '-') . "\n";
echo "Company Username: " . ($company->username ?? '-') . "\n\n";

// Show what would be in the receipt
echo "=== RECEIPT DATA THAT WOULD BE GENERATED ===\n";
echo "customer.name: " . ($customerName ?: '-') . "\n";
echo "customer.account: " . ($customerAccount ?: '-') . "\n";
echo "customer.bank: " . ($customerBank ?: '-') . "\n";
echo "company.username: " . ($company->username ?? '-') . "\n";
echo "company.name: " . ($company->company_name ?? $company->name ?? '-') . "\n";
echo "company.email: " . ($company->email ?? '-') . "\n";

echo "\n=== DONE ===\n";
