<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== FIXING EXISTING TRANSACTIONS WITHOUT transaction_type ===\n\n";

// Map category to transaction_type
$categoryMapping = [
    'virtual_account_credit' => 'va_deposit',
    'transfer' => 'api_transfer',
    'withdrawal' => 'company_withdrawal',
    'refund' => 'refund',
    'fee' => 'fee_charge',
    'kyc_fee' => 'kyc_charge',
    'adjustment' => 'manual_adjustment',
];

// Find all transactions without transaction_type
$transactions = DB::table('transactions')
    ->whereNull('transaction_type')
    ->get();

echo "Found {$transactions->count()} transactions without transaction_type\n\n";

if ($transactions->count() === 0) {
    echo "✅ No transactions to fix!\n";
    exit(0);
}

$updated = 0;
$skipped = 0;

foreach ($transactions as $transaction) {
    $transactionType = null;
    
    // Determine transaction_type based on category
    if (isset($categoryMapping[$transaction->category])) {
        $transactionType = $categoryMapping[$transaction->category];
    } else {
        // Fallback logic based on type and category
        if ($transaction->type === 'credit' && strpos($transaction->category, 'virtual_account') !== false) {
            $transactionType = 'va_deposit';
        } elseif ($transaction->type === 'debit' && strpos($transaction->category, 'transfer') !== false) {
            $transactionType = 'api_transfer';
        } elseif ($transaction->type === 'debit' && strpos($transaction->category, 'withdrawal') !== false) {
            $transactionType = 'company_withdrawal';
        }
    }
    
    if ($transactionType) {
        // Determine settlement_status
        $settlementStatus = 'settled'; // Default for successful transactions
        if (in_array($transaction->status, ['failed', 'reversed'])) {
            $settlementStatus = 'not_applicable';
        } elseif (in_array($transaction->status, ['pending', 'processing'])) {
            $settlementStatus = 'unsettled';
        }
        
        DB::table('transactions')
            ->where('id', $transaction->id)
            ->update([
                'transaction_type' => $transactionType,
                'settlement_status' => $settlementStatus,
                'updated_at' => now(),
            ]);
        
        echo "✅ Updated: {$transaction->transaction_id} | Category: {$transaction->category} → Type: {$transactionType}\n";
        $updated++;
    } else {
        echo "⚠️  Skipped: {$transaction->transaction_id} | Category: {$transaction->category} (no mapping found)\n";
        $skipped++;
    }
}

echo "\n=== SUMMARY ===\n";
echo "Updated: {$updated}\n";
echo "Skipped: {$skipped}\n";
echo "\n✅ Done!\n";
