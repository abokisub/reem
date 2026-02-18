<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFY CHARGES AFTER PAYMENT ===\n\n";

// Get the most recent transaction
$transaction = DB::table('transactions')
    ->orderBy('created_at', 'desc')
    ->first();

if (!$transaction) {
    echo "❌ No transactions found!\n";
    exit(1);
}

echo "LATEST TRANSACTION:\n";
echo "-------------------\n";
echo "Transaction ID: {$transaction->transaction_id}\n";
echo "Type: {$transaction->type} ({$transaction->category})\n";
echo "Status: {$transaction->status}\n";
echo "Created: {$transaction->created_at}\n\n";

echo "AMOUNTS:\n";
echo "--------\n";
echo "Gross Amount (Customer Paid): ₦{$transaction->amount}\n";
echo "Platform Fee: ₦{$transaction->fee}\n";
echo "Net Amount (Company Receives): ₦" . ($transaction->net_amount ?? 'N/A') . "\n";
echo "Total: ₦{$transaction->total_amount}\n\n";

// Get charge configuration
$chargeConfig = DB::table('service_charges')
    ->where('company_id', 1)
    ->where('service_category', 'payment')
    ->where('service_name', 'palmpay_va')
    ->where('is_active', true)
    ->first();

if (!$chargeConfig) {
    echo "❌ Charge configuration not found!\n";
    exit(1);
}

echo "CHARGE CONFIGURATION:\n";
echo "--------------------\n";
echo "Type: {$chargeConfig->charge_type}\n";
echo "Value: {$chargeConfig->charge_value}%\n";
echo "Cap: ₦{$chargeConfig->charge_cap}\n\n";

// Calculate expected fee
$expectedFee = 0;
if ($chargeConfig->charge_type === 'PERCENT') {
    $expectedFee = ($transaction->amount * $chargeConfig->charge_value) / 100;
    
    if ($chargeConfig->charge_cap && $expectedFee > $chargeConfig->charge_cap) {
        $expectedFee = $chargeConfig->charge_cap;
    }
}

$expectedFee = round($expectedFee, 2);
$expectedNet = $transaction->amount - $expectedFee;

echo "VERIFICATION:\n";
echo "-------------\n";
echo "Expected Fee: ₦{$expectedFee}\n";
echo "Actual Fee: ₦{$transaction->fee}\n";

if (abs($transaction->fee - $expectedFee) < 0.01) {
    echo "✅ FEE IS CORRECT!\n\n";
} else {
    echo "❌ FEE MISMATCH!\n\n";
}

echo "Expected Net: ₦{$expectedNet}\n";
echo "Actual Net: ₦" . ($transaction->net_amount ?? 'N/A') . "\n";

if ($transaction->net_amount && abs($transaction->net_amount - $expectedNet) < 0.01) {
    echo "✅ NET AMOUNT IS CORRECT!\n\n";
} elseif (!$transaction->net_amount) {
    echo "⚠️  NET AMOUNT NOT SET (old transaction?)\n\n";
} else {
    echo "❌ NET AMOUNT MISMATCH!\n\n";
}

// Check wallet balance
$wallet = DB::table('company_wallets')
    ->where('company_id', $transaction->company_id)
    ->where('currency', 'NGN')
    ->first();

if ($wallet) {
    echo "COMPANY WALLET:\n";
    echo "---------------\n";
    echo "Company ID: {$transaction->company_id}\n";
    echo "Current Balance: ₦{$wallet->balance}\n";
    
    if ($transaction->balance_before !== null) {
        echo "Balance Before: ₦{$transaction->balance_before}\n";
        echo "Balance After: ₦{$transaction->balance_after}\n";
        
        $credited = $transaction->balance_after - $transaction->balance_before;
        echo "Amount Credited: ₦{$credited}\n";
        
        if (abs($credited - $expectedNet) < 0.01) {
            echo "✅ WALLET CREDITED WITH NET AMOUNT!\n";
        } else {
            echo "⚠️  Wallet credited with: ₦{$credited} (expected: ₦{$expectedNet})\n";
        }
    } else {
        echo "⚠️  Transaction in settlement queue (not credited yet)\n";
    }
}

// Check metadata
if ($transaction->metadata) {
    $metadata = json_decode($transaction->metadata, true);
    
    echo "\nTRANSACTION METADATA:\n";
    echo "--------------------\n";
    
    if (isset($metadata['charge_type'])) {
        echo "Charge Type: {$metadata['charge_type']}\n";
        echo "Charge Value: {$metadata['charge_value']}\n";
        echo "Charge Cap: " . ($metadata['charge_cap'] ?? 'N/A') . "\n";
        echo "✅ CHARGE DETAILS STORED IN METADATA!\n";
    } else {
        echo "⚠️  No charge details in metadata (old transaction?)\n";
    }
    
    if (isset($metadata['settlement_status'])) {
        echo "\nSettlement Status: {$metadata['settlement_status']}\n";
        echo "Scheduled Date: {$metadata['scheduled_settlement_date']}\n";
    }
}

echo "\n=== SUMMARY ===\n";

$allGood = true;

if (abs($transaction->fee - $expectedFee) >= 0.01) {
    echo "❌ Fee calculation is incorrect\n";
    $allGood = false;
} else {
    echo "✅ Fee calculation is correct\n";
}

if (!$transaction->net_amount) {
    echo "⚠️  Net amount not set (old transaction)\n";
    $allGood = false;
} elseif (abs($transaction->net_amount - $expectedNet) >= 0.01) {
    echo "❌ Net amount is incorrect\n";
    $allGood = false;
} else {
    echo "✅ Net amount is correct\n";
}

if ($allGood) {
    echo "\n🎉 ALL CHECKS PASSED! Charges are working correctly!\n";
} else {
    echo "\n⚠️  Some checks failed. Review the details above.\n";
}

echo "\n=== END ===\n";
