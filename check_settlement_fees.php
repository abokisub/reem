<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CHECKING SETTLEMENT WITHDRAWAL FEE CONFIGURATION ===\n\n";

// Get settings
$settings = DB::table('settings')->first();

if ($settings) {
    echo "Current Settlement Withdrawal (PalmPay) Fee Configuration:\n";
    echo "-----------------------------------------------------------\n";
    echo "Type: " . ($settings->payout_palmpay_charge_type ?? 'NOT SET') . "\n";
    echo "Value: " . ($settings->payout_palmpay_charge_value ?? 'NOT SET') . "\n";
    echo "Cap: " . ($settings->payout_palmpay_charge_cap ?? 'NOT SET') . "\n\n";
    
    echo "External Transfer (Other Banks) Fee Configuration:\n";
    echo "-----------------------------------------------------------\n";
    echo "Type: " . ($settings->transfer_charge_type ?? 'NOT SET') . "\n";
    echo "Value: " . ($settings->transfer_charge_value ?? 'NOT SET') . "\n";
    echo "Cap: " . ($settings->transfer_charge_cap ?? 'NOT SET') . "\n\n";
    
    // Calculate example fees
    $testAmount = 100;
    
    echo "Example Fee Calculations for ₦{$testAmount}:\n";
    echo "-----------------------------------------------------------\n";
    
    // Settlement Withdrawal Fee
    $settlementType = $settings->payout_palmpay_charge_type ?? 'FLAT';
    $settlementValue = $settings->payout_palmpay_charge_value ?? 10;
    $settlementCap = $settings->payout_palmpay_charge_cap ?? 0;
    
    if ($settlementType == 'PERCENTAGE' || $settlementType == 'PERCENT') {
        $settlementFee = ($testAmount / 100) * $settlementValue;
        if ($settlementCap > 0 && $settlementFee > $settlementCap) {
            $settlementFee = $settlementCap;
        }
    } else {
        $settlementFee = $settlementValue;
    }
    
    echo "Settlement Withdrawal: ₦{$settlementFee} fee\n";
    echo "  → You withdraw ₦{$testAmount}, system deducts ₦" . ($testAmount + $settlementFee) . " from wallet\n";
    echo "  → ₦{$testAmount} sent to your settlement account\n\n";
    
    // External Transfer Fee
    $transferType = $settings->transfer_charge_type ?? 'FLAT';
    $transferValue = $settings->transfer_charge_value ?? 0;
    $transferCap = $settings->transfer_charge_cap ?? 0;
    
    if ($transferType == 'PERCENTAGE' || $transferType == 'PERCENT') {
        $transferFee = ($testAmount / 100) * $transferValue;
        if ($transferCap > 0 && $transferFee > $transferCap) {
            $transferFee = $transferCap;
        }
    } else {
        $transferFee = $transferValue;
    }
    
    echo "External Transfer: ₦{$transferFee} fee\n";
    echo "  → You transfer ₦{$testAmount}, system deducts ₦" . ($testAmount + $transferFee) . " from wallet\n";
    echo "  → ₦{$testAmount} sent to recipient\n\n";
    
} else {
    echo "ERROR: Settings not found!\n";
}

echo "\n=== RECENT TRANSFER TRANSACTIONS ===\n\n";

$recentTransfers = DB::table('transactions')
    ->where('category', 'transfer_out')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($recentTransfers as $txn) {
    echo "Reference: {$txn->reference}\n";
    echo "Amount: ₦{$txn->amount}\n";
    echo "Fee: ₦{$txn->fee}\n";
    echo "Total Deducted: ₦{$txn->total_amount}\n";
    echo "Status: {$txn->status}\n";
    echo "Created: {$txn->created_at}\n";
    echo "---\n";
}
