<?php

/**
 * Check if KYC charges are configured and working
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "========================================\n";
echo "KYC CHARGES VERIFICATION\n";
echo "========================================\n\n";

// 1. Check service_charges table for KYC charges
echo "1. Checking service_charges table...\n";
echo str_repeat("-", 60) . "\n";

$kycCharges = DB::table('service_charges')
    ->where(function($query) {
        $query->where('service_name', 'LIKE', '%kyc%')
              ->orWhere('service_name', 'LIKE', '%bvn%')
              ->orWhere('service_name', 'LIKE', '%nin%')
              ->orWhere('service_name', 'LIKE', '%verification%');
    })
    ->get();

if ($kycCharges->isEmpty()) {
    echo "❌ NO KYC CHARGES FOUND IN DATABASE!\n\n";
    echo "Missing charges:\n";
    echo "  - BVN Verification Fee\n";
    echo "  - NIN Verification Fee\n";
    echo "  - Bank Account Verification Fee\n\n";
} else {
    echo "✅ Found " . $kycCharges->count() . " KYC charge(s):\n\n";
    foreach ($kycCharges as $charge) {
        echo "Service: {$charge->service_name}\n";
        echo "  Type: {$charge->charge_type}\n";
        echo "  Value: ₦{$charge->charge_value}\n";
        echo "  Cap: " . ($charge->charge_cap ? "₦{$charge->charge_cap}" : "None") . "\n";
        echo "  Status: " . ($charge->status ? '✅ Active' : '❌ Inactive') . "\n";
        echo str_repeat("-", 60) . "\n";
    }
}

// 2. Check if KYC transactions exist
echo "\n2. Checking for KYC charge transactions...\n";
echo str_repeat("-", 60) . "\n";

$kycTransactions = DB::table('transactions')
    ->where('transaction_type', 'kyc_charge')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

if ($kycTransactions->isEmpty()) {
    echo "❌ NO KYC CHARGE TRANSACTIONS FOUND!\n";
    echo "   This means the system is NOT deducting KYC charges.\n\n";
} else {
    echo "✅ Found " . $kycTransactions->count() . " recent KYC charge transaction(s):\n\n";
    foreach ($kycTransactions as $txn) {
        echo "Transaction: {$txn->reference}\n";
        echo "  Amount: ₦{$txn->amount}\n";
        echo "  Company ID: {$txn->company_id}\n";
        echo "  Date: {$txn->created_at}\n";
        echo str_repeat("-", 60) . "\n";
    }
}

// 3. Check KYC Service implementation
echo "\n3. Checking KYC Service implementation...\n";
echo str_repeat("-", 60) . "\n";

$kycServiceFile = file_get_contents('app/Services/KYC/KycService.php');

$hasChargeDeduction = strpos($kycServiceFile, 'deduct') !== false || 
                      strpos($kycServiceFile, 'charge') !== false ||
                      strpos($kycServiceFile, 'Transaction::create') !== false;

if ($hasChargeDeduction) {
    echo "✅ KYC Service appears to handle charges\n";
} else {
    echo "❌ KYC Service does NOT deduct charges!\n";
    echo "   The verifyBVN() and verifyNIN() methods need to be updated.\n";
}

// 4. Summary
echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n\n";

$hasCharges = !$kycCharges->isEmpty();
$hasTransactions = !$kycTransactions->isEmpty();
$hasImplementation = $hasChargeDeduction;

if ($hasCharges && $hasTransactions && $hasImplementation) {
    echo "✅ KYC CHARGES: FULLY WORKING\n";
} else {
    echo "❌ KYC CHARGES: NOT WORKING PROPERLY\n\n";
    echo "Issues found:\n";
    if (!$hasCharges) {
        echo "  ❌ No KYC charges configured in database\n";
    }
    if (!$hasTransactions) {
        echo "  ❌ No KYC charge transactions recorded\n";
    }
    if (!$hasImplementation) {
        echo "  ❌ KYC Service doesn't deduct charges\n";
    }
    echo "\n";
    echo "ACTION REQUIRED: Fix KYC charge system\n";
}

echo "\n";
