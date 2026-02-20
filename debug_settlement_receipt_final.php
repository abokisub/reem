<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Transaction;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

echo "Final Settlement Receipt Debug\n";
echo "===============================\n\n";

// Get the settlement withdrawal transaction
$transaction = Transaction::where('transaction_type', 'settlement_withdrawal')
    ->orderBy('id', 'desc')
    ->first();

if (!$transaction) {
    echo "❌ No settlement_withdrawal transaction found\n";
    exit;
}

echo "Transaction ID: {$transaction->id}\n";
echo "Company ID: {$transaction->company_id}\n\n";

// Method 1: Get company via Eloquent relationship
echo "Method 1: Via Eloquent Relationship\n";
echo "------------------------------------\n";
$company = $transaction->company;
echo "settlement_account_number: " . ($company->settlement_account_number ?? 'NULL') . "\n";
echo "account_number: " . ($company->account_number ?? 'NULL') . "\n\n";

// Method 2: Fresh query
echo "Method 2: Fresh Company Query\n";
echo "------------------------------\n";
$companyFresh = Company::find($transaction->company_id);
echo "settlement_account_number: " . ($companyFresh->settlement_account_number ?? 'NULL') . "\n";
echo "account_number: " . ($companyFresh->account_number ?? 'NULL') . "\n\n";

// Method 3: Raw DB query
echo "Method 3: Raw Database Query\n";
echo "-----------------------------\n";
$companyRaw = DB::table('companies')->where('id', $transaction->company_id)->first();
echo "settlement_account_number: " . ($companyRaw->settlement_account_number ?? 'NULL') . "\n";
echo "account_number: " . ($companyRaw->account_number ?? 'NULL') . "\n\n";

// Test the exact logic from ReceiptService
echo "ReceiptService Logic Test\n";
echo "-------------------------\n";
$senderAccount = $company->settlement_account_number ?? $company->account_number ?? '';
echo "Result: '{$senderAccount}'\n";
echo "Is empty? " . (empty($senderAccount) ? 'YES ❌' : 'NO ✅') . "\n\n";

if (empty($senderAccount)) {
    echo "🔍 PROBLEM FOUND: The value is empty even though it exists in DB!\n";
    echo "This means Laravel is not loading the attribute properly.\n\n";
    
    // Check if it's in the attributes array
    echo "Company Attributes:\n";
    print_r($company->getAttributes());
} else {
    echo "✅ Value is correctly loaded: {$senderAccount}\n";
}

echo "\n✅ Done!\n";
