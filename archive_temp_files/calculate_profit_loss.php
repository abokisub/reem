<?php

/**
 * Profit/Loss Calculator for Payment Gateway Operations
 * 
 * Costs:
 * - PalmPay: 0.5% capped at ₦500 per transaction
 * - Transfer Fee: ₦25 per transfer
 * 
 * Revenue:
 * - Your service charges to customers
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== PAYMENT GATEWAY PROFIT/LOSS ANALYSIS ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Get all transactions from today or specify date range
$startDate = date('Y-m-d 00:00:00');
$endDate = date('Y-m-d 23:59:59');

echo "Analyzing transactions from: $startDate to $endDate\n";
echo str_repeat("=", 60) . "\n\n";

// Get all successful transactions
$transactions = DB::table('transactions')
    ->where('status', 'success')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->get();

$totalRevenue = 0;
$totalPalmPayFees = 0;
$totalTransferFees = 0;
$transactionCount = 0;

echo "Transaction Details:\n";
echo str_repeat("-", 60) . "\n";

foreach ($transactions as $transaction) {
    $amount = (float) $transaction->amount;
    
    // Calculate PalmPay fee (0.5% capped at ₦500)
    $palmpayFee = min(($amount * 0.005), 500);
    
    // Transfer fee (₦25 per transaction)
    $transferFee = 25;
    
    // Your service charge (from transaction record)
    $serviceCharge = (float) ($transaction->fee ?? 0);
    
    // Calculate profit/loss for this transaction
    $totalCost = $palmpayFee + $transferFee;
    $profit = $serviceCharge - $totalCost;
    
    $totalRevenue += $serviceCharge;
    $totalPalmPayFees += $palmpayFee;
    $totalTransferFees += $transferFee;
    $transactionCount++;
    
    echo sprintf(
        "TXN: %s | Amount: ₦%s | Your Fee: ₦%s | PalmPay: ₦%s | Transfer: ₦%s | Profit: ₦%s\n",
        $transaction->reference,
        number_format($amount, 2),
        number_format($serviceCharge, 2),
        number_format($palmpayFee, 2),
        number_format($transferFee, 2),
        number_format($profit, 2)
    );
}

echo str_repeat("=", 60) . "\n\n";

// Summary
$totalCosts = $totalPalmPayFees + $totalTransferFees;
$netProfitLoss = $totalRevenue - $totalCosts;
$profitMargin = $totalRevenue > 0 ? (($netProfitLoss / $totalRevenue) * 100) : 0;

echo "SUMMARY:\n";
echo str_repeat("-", 60) . "\n";
echo sprintf("Total Transactions: %d\n", $transactionCount);
echo sprintf("Total Revenue (Your Fees): ₦%s\n", number_format($totalRevenue, 2));
echo "\n";
echo "COSTS:\n";
echo sprintf("  - PalmPay Fees (0.5%% capped ₦500): ₦%s\n", number_format($totalPalmPayFees, 2));
echo sprintf("  - Transfer Fees (₦25 each): ₦%s\n", number_format($totalTransferFees, 2));
echo sprintf("  - Total Costs: ₦%s\n", number_format($totalCosts, 2));
echo "\n";
echo sprintf("NET PROFIT/LOSS: ₦%s\n", number_format($netProfitLoss, 2));
echo sprintf("Profit Margin: %.2f%%\n", $profitMargin);
echo "\n";

if ($netProfitLoss > 0) {
    echo "✅ YOU ARE MAKING PROFIT!\n";
} elseif ($netProfitLoss < 0) {
    echo "❌ YOU ARE MAKING A LOSS!\n";
    echo "⚠️  Consider increasing your service charges.\n";
} else {
    echo "⚖️  BREAK EVEN - No profit, no loss.\n";
}

echo "\n";
echo "RECOMMENDATIONS:\n";
echo str_repeat("-", 60) . "\n";

// Calculate break-even fee
$avgTransactionAmount = $transactionCount > 0 ? 
    DB::table('transactions')
        ->where('status', 'success')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->avg('amount') : 0;

if ($avgTransactionAmount > 0) {
    $avgPalmPayFee = min(($avgTransactionAmount * 0.005), 500);
    $avgTotalCost = $avgPalmPayFee + 25;
    $recommendedMinFee = $avgTotalCost * 1.2; // 20% profit margin
    
    echo sprintf("Average Transaction Amount: ₦%s\n", number_format($avgTransactionAmount, 2));
    echo sprintf("Average Cost per Transaction: ₦%s\n", number_format($avgTotalCost, 2));
    echo sprintf("Recommended Minimum Fee (20%% margin): ₦%s\n", number_format($recommendedMinFee, 2));
    echo sprintf("Recommended Fee Percentage: %.2f%%\n", ($recommendedMinFee / $avgTransactionAmount) * 100);
}

echo "\n";
echo "To run for specific date range, modify \$startDate and \$endDate in the script.\n";
echo "\n";
