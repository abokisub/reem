<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Services\ReceiptService;

echo "Testing Settlement Receipt Data\n";
echo "================================\n\n";

// Get the settlement withdrawal transaction
$transaction = Transaction::where('transaction_type', 'settlement_withdrawal')
    ->orderBy('id', 'desc')
    ->first();

if (!$transaction) {
    echo "❌ No settlement_withdrawal transaction found\n";
    exit;
}

echo "Transaction ID: {$transaction->id}\n";
echo "Reference: {$transaction->transid}\n\n";

// Generate receipt data using ReceiptService
$receiptService = new ReceiptService();
$receiptData = $receiptService->generateReceiptData($transaction);

echo "Receipt Data Generated:\n";
echo "=======================\n\n";

echo "Sender Details:\n";
echo "  Name: " . ($receiptData['senderName'] ?? 'NULL') . "\n";
echo "  Account: " . ($receiptData['senderAccount'] ?? 'NULL') . "\n";
echo "  Bank: " . ($receiptData['senderBank'] ?? 'NULL') . "\n\n";

echo "Recipient Details:\n";
echo "  Name: " . ($receiptData['recipientName'] ?? 'NULL') . "\n";
echo "  Account: " . ($receiptData['recipientAccount'] ?? 'NULL') . "\n";
echo "  Bank: " . ($receiptData['recipientBank'] ?? 'NULL') . "\n\n";

echo "Company Data from Transaction:\n";
$company = $transaction->company;
echo "  company->name: " . ($company->name ?? 'NULL') . "\n";
echo "  company->company_name: " . ($company->company_name ?? 'NULL') . "\n";
echo "  company->account_number: " . ($company->account_number ?? 'NULL') . "\n";
echo "  company->settlement_account_number: " . ($company->settlement_account_number ?? 'NULL') . "\n";
echo "  company->bank_name: " . ($company->bank_name ?? 'NULL') . "\n";
echo "  company->settlement_bank_name: " . ($company->settlement_bank_name ?? 'NULL') . "\n\n";

echo "✅ Done!\n";
