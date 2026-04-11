// Run this in tinker to see detailed breakdown per transaction

$txns = DB::table('transactions')
    ->where('status', 'success')
    ->where('created_at', '>=', now()->startOfDay())
    ->get();

echo "\n=== DETAILED TRANSACTION ANALYSIS ===\n";
echo "Total Transactions: " . $txns->count() . "\n\n";

$totalRevenue = 0;
$totalCosts = 0;
$lossCount = 0;
$profitCount = 0;

foreach ($txns as $txn) {
    $amount = (float) $txn->amount;
    $yourFee = (float) ($txn->fee ?? 0);
    $palmpayFee = min(($amount * 0.005), 500);
    $transferFee = 25;
    $totalCost = $palmpayFee + $transferFee;
    $profit = $yourFee - $totalCost;
    
    $totalRevenue += $yourFee;
    $totalCosts += $totalCost;
    
    if ($profit < 0) {
        $lossCount++;
        echo "❌ LOSS: ";
    } else {
        $profitCount++;
        echo "✅ PROFIT: ";
    }
    
    echo sprintf(
        "Ref: %s | Amount: ₦%s | Your Fee: ₦%s | Costs: ₦%s (PalmPay: ₦%s + Transfer: ₦25) | P/L: ₦%s\n",
        substr($txn->reference, 0, 15),
        number_format($amount, 2),
        number_format($yourFee, 2),
        number_format($totalCost, 2),
        number_format($palmpayFee, 2),
        number_format($profit, 2)
    );
}

echo "\n=== SUMMARY ===\n";
echo "Profitable Transactions: $profitCount\n";
echo "Loss-Making Transactions: $lossCount\n";
echo "Total Revenue: ₦" . number_format($totalRevenue, 2) . "\n";
echo "Total Costs: ₦" . number_format($totalCosts, 2) . "\n";
echo "Net P/L: ₦" . number_format($totalRevenue - $totalCosts, 2) . "\n\n";

// Calculate recommended fee
$avgAmount = $txns->avg('amount');
$avgPalmpayFee = min(($avgAmount * 0.005), 500);
$avgCost = $avgPalmpayFee + 25;
$recommendedFee = $avgCost * 1.3; // 30% profit margin
$recommendedPercent = ($recommendedFee / $avgAmount) * 100;

echo "=== RECOMMENDATIONS ===\n";
echo "Average Transaction: ₦" . number_format($avgAmount, 2) . "\n";
echo "Average Cost: ₦" . number_format($avgCost, 2) . "\n";
echo "Current Average Fee: ₦" . number_format($txns->avg('fee'), 2) . "\n";
echo "\nTO MAKE 30% PROFIT:\n";
echo "Recommended Fee: ₦" . number_format($recommendedFee, 2) . " per transaction\n";
echo "Recommended Percentage: " . number_format($recommendedPercent, 2) . "%\n";
echo "\nTO BREAK EVEN:\n";
echo "Minimum Fee: ₦" . number_format($avgCost, 2) . " per transaction\n";
echo "Minimum Percentage: " . number_format(($avgCost / $avgAmount) * 100, 2) . "%\n";
