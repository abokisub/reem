<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== LIVE SERVER: SETTLEMENT WITHDRAWAL FEE CHECK ===\n\n";

// Get settings
$settings = DB::table('settings')->first();

if ($settings) {
    echo "1. CURRENT FEE CONFIGURATION:\n";
    echo "========================================\n";
    echo "Settlement Withdrawal (PalmPay) Fee:\n";
    echo "  Type: " . ($settings->payout_palmpay_charge_type ?? 'NOT SET') . "\n";
    echo "  Value: " . ($settings->payout_palmpay_charge_value ?? 'NOT SET') . "\n";
    echo "  Cap: " . ($settings->payout_palmpay_charge_cap ?? 'NOT SET') . "\n\n";
    
    echo "External Transfer (Other Banks) Fee:\n";
    echo "  Type: " . ($settings->transfer_charge_type ?? 'NOT SET') . "\n";
    echo "  Value: " . ($settings->transfer_charge_value ?? 'NOT SET') . "\n";
    echo "  Cap: " . ($settings->transfer_charge_cap ?? 'NOT SET') . "\n\n";
}

echo "\n2. YOUR COMPANY SETTLEMENT ACCOUNT:\n";
echo "========================================\n";

// Get all companies with settlement accounts
$companies = DB::table('companies')
    ->whereNotNull('settlement_account_number')
    ->select('id', 'name', 'settlement_account_number', 'settlement_bank_name', 'bank_code')
    ->get();

foreach ($companies as $company) {
    echo "Company ID: {$company->id}\n";
    echo "Name: {$company->name}\n";
    echo "Settlement Account: " . ($company->settlement_account_number ?? 'NOT SET') . "\n";
    echo "Bank Code: " . ($company->bank_code ?? 'NOT SET') . "\n";
    echo "Bank Name: " . ($company->settlement_bank_name ?? 'NOT SET') . "\n";
    echo "---\n";
}

echo "\n3. RECENT TRANSFER FROM LOGS:\n";
echo "========================================\n";
echo "Account Number: 7040540018\n";
echo "Bank Code: 100004\n";
echo "Amount: ₦100\n";
echo "Fee Charged: ₦0.50\n\n";

echo "4. CHECKING IF TRANSFER MATCHES SETTLEMENT ACCOUNT:\n";
echo "========================================\n";

$match = DB::table('companies')
    ->where('settlement_account_number', '7040540018')
    ->where('bank_code', '100004')
    ->first();

if ($match) {
    echo "✓ MATCH FOUND!\n";
    echo "Company: {$match->name} (ID: {$match->id})\n";
    echo "This SHOULD be detected as Settlement Withdrawal\n";
    echo "Expected fee: ₦" . ($settings->payout_palmpay_charge_value ?? '10') . "\n";
    echo "Actual fee charged: ₦0.50\n";
    echo "\n⚠️ PROBLEM: Fee mismatch! Settlement withdrawal fee not applied correctly.\n";
} else {
    echo "✗ NO MATCH FOUND\n";
    echo "This transfer is treated as External Transfer\n";
    echo "Expected fee: ₦" . ($settings->transfer_charge_value ?? '0') . "\n";
    echo "Actual fee charged: ₦0.50\n";
    echo "\n⚠️ PROBLEM: Settlement account not configured correctly.\n";
}

echo "\n5. LAST 3 TRANSFERS:\n";
echo "========================================\n";

$recentTransfers = DB::table('transactions')
    ->where('category', 'transfer_out')
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get(['reference', 'amount', 'fee', 'total_amount', 'status', 'recipient_account_number', 'recipient_bank_code', 'created_at']);

foreach ($recentTransfers as $txn) {
    echo "Reference: {$txn->reference}\n";
    echo "Amount: ₦{$txn->amount}\n";
    echo "Fee: ₦{$txn->fee}\n";
    echo "Total: ₦{$txn->total_amount}\n";
    echo "To Account: {$txn->recipient_account_number}\n";
    echo "Bank Code: {$txn->recipient_bank_code}\n";
    echo "Status: {$txn->status}\n";
    echo "Date: {$txn->created_at}\n";
    
    // Check if this matches settlement account
    $isSettlement = DB::table('companies')
        ->where('settlement_account_number', $txn->recipient_account_number)
        ->where('bank_code', $txn->recipient_bank_code)
        ->exists();
    
    echo "Is Settlement Withdrawal: " . ($isSettlement ? 'YES' : 'NO') . "\n";
    echo "---\n";
}

echo "\n=== END OF CHECK ===\n";
