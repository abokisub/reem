<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Services\ReceiptService;

echo "=== DEBUGGING RECEIPT GENERATION ===\n\n";

// Find the transaction
$transaction = Transaction::with(['company', 'company.virtualAccounts'])->find(2);

if (!$transaction) {
    echo "❌ Transaction with ID 2 not found\n";
    exit(1);
}

echo "✅ Transaction found: {$transaction->transaction_id}\n";
echo "Company ID: {$transaction->company_id}\n";
echo "Type: {$transaction->type}\n";
echo "Transaction Type: {$transaction->transaction_type}\n\n";

echo "=== COMPANY INFO ===\n";
$company = $transaction->company;
echo "Company Name: " . ($company->company_name ?? $company->name ?? 'N/A') . "\n";
echo "Company Email: " . ($company->email ?? 'N/A') . "\n";
echo "Company Username: " . ($company->username ?? 'N/A') . "\n\n";

echo "=== VIRTUAL ACCOUNTS ===\n";
$virtualAccounts = $company->virtualAccounts;
echo "Virtual Accounts Count: " . $virtualAccounts->count() . "\n";

if ($virtualAccounts->count() > 0) {
    $va = $virtualAccounts->first();
    echo "✅ Virtual Account Found:\n";
    
    // Check both possible column name formats
    echo "  Account Number (palmpay_*): " . ($va->palmpay_account_number ?? 'N/A') . "\n";
    echo "  Account Number (generic): " . ($va->account_number ?? 'N/A') . "\n";
    echo "  Account Name (palmpay_*): " . ($va->palmpay_account_name ?? 'N/A') . "\n";
    echo "  Account Name (generic): " . ($va->account_name ?? 'N/A') . "\n";
    echo "  Bank Name (palmpay_*): " . ($va->palmpay_bank_name ?? 'N/A') . "\n";
    echo "  Bank Name (generic): " . ($va->bank_name ?? 'N/A') . "\n";
    
    echo "\n  FINAL VALUES (with fallback logic):\n";
    $recipientName = $va->palmpay_account_name ?? $va->account_name ?? '';
    $recipientAccount = $va->palmpay_account_number ?? $va->account_number ?? '';
    $recipientBank = $va->palmpay_bank_name ?? $va->bank_name ?? 'PalmPay';
    
    echo "  → Recipient Name: " . ($recipientName ?: 'EMPTY') . "\n";
    echo "  → Recipient Account: " . ($recipientAccount ?: 'EMPTY') . "\n";
    echo "  → Recipient Bank: " . ($recipientBank ?: 'EMPTY') . "\n";
} else {
    echo "❌ No virtual accounts found\n";
}

echo "\n=== METADATA ===\n";
$metadata = is_array($transaction->metadata) ? $transaction->metadata : json_decode($transaction->metadata, true) ?? [];
echo "Sender Name: " . ($metadata['sender_name'] ?? 'N/A') . "\n";
echo "Sender Account: " . ($metadata['sender_account'] ?? 'N/A') . "\n";
echo "Sender Bank: " . ($metadata['sender_bank'] ?? 'N/A') . "\n\n";

echo "=== SIMULATING RECEIPT DATA GENERATION ===\n";

// Simulate what ReceiptService does
$isCredit = $transaction->type === 'credit' || $transaction->transaction_type === 'va_deposit';
echo "Is Credit Transaction: " . ($isCredit ? 'YES' : 'NO') . "\n\n";

if ($isCredit) {
    echo "Getting recipient from virtual account...\n";
    $virtualAccount = $company->virtualAccounts()->first();
    
    if ($virtualAccount) {
        echo "✅ Virtual Account Retrieved:\n";
        
        // Use fallback logic like ReceiptService
        $recipientName = $virtualAccount->palmpay_account_name 
            ?? $virtualAccount->account_name 
            ?? '';
        $recipientAccount = $virtualAccount->palmpay_account_number 
            ?? $virtualAccount->account_number 
            ?? '';
        $recipientBank = $virtualAccount->palmpay_bank_name 
            ?? $virtualAccount->bank_name 
            ?? 'PalmPay';
        
        echo "  Recipient Name: " . ($recipientName ?: 'EMPTY') . "\n";
        echo "  Recipient Account: " . ($recipientAccount ?: 'EMPTY') . "\n";
        echo "  Recipient Bank: " . ($recipientBank ?: 'EMPTY') . "\n";
    } else {
        echo "❌ Virtual Account NOT Retrieved\n";
        echo "This is why recipient shows N/A!\n";
    }
} else {
    echo "Getting recipient from transaction fields...\n";
    echo "  Recipient Name: " . ($transaction->recipient_account_name ?? 'N/A') . "\n";
    echo "  Recipient Account: " . ($transaction->recipient_account_number ?? 'N/A') . "\n";
    echo "  Recipient Bank: " . ($transaction->recipient_bank_name ?? 'N/A') . "\n";
}

echo "\n=== TESTING ACTUAL RECEIPT SERVICE ===\n";

try {
    $receiptService = new ReceiptService();
    
    // Use reflection to access protected method
    $reflection = new ReflectionClass($receiptService);
    $method = $reflection->getMethod('getReceiptData');
    $method->setAccessible(true);
    
    $receiptData = $method->invoke($receiptService, $transaction);
    
    echo "✅ Receipt data generated successfully\n\n";
    echo "SENDER:\n";
    echo "  Name: " . $receiptData['sender']['name'] . "\n";
    echo "  Account: " . $receiptData['sender']['account'] . "\n";
    echo "  Bank: " . $receiptData['sender']['bank'] . "\n\n";
    
    echo "RECIPIENT:\n";
    echo "  Name: " . $receiptData['recipient']['name'] . "\n";
    echo "  Account: " . $receiptData['recipient']['account'] . "\n";
    echo "  Bank: " . $receiptData['recipient']['bank'] . "\n\n";
    
    if ($receiptData['recipient']['name'] === '-' || $receiptData['recipient']['name'] === 'N/A') {
        echo "❌ PROBLEM: Recipient name is still showing as dash/N/A\n";
        echo "This means the virtual account is not being fetched correctly.\n";
    } else {
        echo "✅ SUCCESS: Recipient details are correct!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DONE ===\n";
