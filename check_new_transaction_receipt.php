<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Services\ReceiptService;

echo "=== CHECKING NEW TRANSACTION ===\n\n";

// Get the latest transaction
$transaction = Transaction::with(['company', 'company.virtualAccounts'])
    ->orderBy('id', 'desc')
    ->first();

if (!$transaction) {
    echo "❌ No transactions found\n";
    exit(1);
}

echo "✅ Latest Transaction: {$transaction->transaction_id}\n";
echo "Transaction ID (database): {$transaction->id}\n";
echo "Company ID: {$transaction->company_id}\n";
echo "Type: {$transaction->type}\n";
echo "Transaction Type: {$transaction->transaction_type}\n";
echo "Amount: ₦{$transaction->amount}\n";
echo "Created: {$transaction->created_at}\n\n";

echo "=== COMPANY INFO ===\n";
$company = $transaction->company;
if ($company) {
    echo "Company Name: " . ($company->company_name ?? $company->name ?? 'N/A') . "\n";
    echo "Company Email: " . ($company->email ?? 'N/A') . "\n\n";
    
    echo "=== VIRTUAL ACCOUNTS ===\n";
    $virtualAccounts = $company->virtualAccounts;
    echo "Count: " . $virtualAccounts->count() . "\n";
    
    if ($virtualAccounts->count() > 0) {
        foreach ($virtualAccounts as $index => $va) {
            echo "\nVirtual Account #{$index}:\n";
            echo "  ID: {$va->id}\n";
            echo "  palmpay_account_number: " . ($va->palmpay_account_number ?? 'NULL') . "\n";
            echo "  palmpay_account_name: " . ($va->palmpay_account_name ?? 'NULL') . "\n";
            echo "  account_number: " . ($va->account_number ?? 'NULL') . "\n";
            echo "  account_name: " . ($va->account_name ?? 'NULL') . "\n";
            echo "  bank_name: " . ($va->bank_name ?? 'NULL') . "\n";
            echo "  palmpay_bank_name: " . ($va->palmpay_bank_name ?? 'NULL') . "\n";
        }
    }
} else {
    echo "❌ Company not found!\n";
}

echo "\n=== TESTING RECEIPT SERVICE ===\n";

try {
    $receiptService = new ReceiptService();
    
    // Use reflection to access protected method
    $reflection = new ReflectionClass($receiptService);
    $method = $reflection->getMethod('getReceiptData');
    $method->setAccessible(true);
    
    $receiptData = $method->invoke($receiptService, $transaction);
    
    echo "✅ Receipt data generated\n\n";
    echo "SENDER:\n";
    echo "  Name: " . $receiptData['sender']['name'] . "\n";
    echo "  Account: " . $receiptData['sender']['account'] . "\n";
    echo "  Bank: " . $receiptData['sender']['bank'] . "\n\n";
    
    echo "RECIPIENT:\n";
    echo "  Name: " . $receiptData['recipient']['name'] . "\n";
    echo "  Account: " . $receiptData['recipient']['account'] . "\n";
    echo "  Bank: " . $receiptData['recipient']['bank'] . "\n\n";
    
    if ($receiptData['recipient']['name'] === '-' || $receiptData['recipient']['name'] === 'N/A') {
        echo "❌ PROBLEM: Recipient is showing as dash/N/A\n";
        echo "\nDEBUGGING:\n";
        echo "Is Credit: " . ($receiptData['is_credit'] ? 'YES' : 'NO') . "\n";
        echo "Transaction Type: " . $transaction->transaction_type . "\n";
        echo "Transaction->type: " . $transaction->type . "\n";
    } else {
        echo "✅ SUCCESS: Recipient details are correct!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== DONE ===\n";
