// Copy and paste this into: php artisan tinker

// === PROFIT/LOSS CALCULATOR ===
// PalmPay: 0.5% capped at ₦500
// Transfer: ₦25 per transaction

$today = now()->startOfDay();
$transactions = DB::table('transactions')
    ->where('status', 'success')
    ->where('created_at', '>=', $today)
    ->get();

$totalRevenue = 0;
$totalPalmPayFees = 0;
$totalTransferFees = 0;

foreach ($transactions as $txn) {
    $amount = (float) $txn->amount;
    $palmpayFee = min(($amount * 0.005), 500);
    $transferFee = 25;
    $yourFee = (float) ($txn->fee ?? 0);
    
    $totalRevenue += $yourFee;
    $totalPalmPayFees += $palmpayFee;
    $totalTransferFees += $transferFee;
}

$totalCosts = $totalPalmPayFees + $totalTransferFees;
$netProfit = $totalRevenue - $totalCosts;

echo "\n=== TODAY'S PROFIT/LOSS ===\n";
echo "Transactions: " . $transactions->count() . "\n";
echo "Your Revenue: ₦" . number_format($totalRevenue, 2) . "\n";
echo "PalmPay Fees: ₦" . number_format($totalPalmPayFees, 2) . "\n";
echo "Transfer Fees: ₦" . number_format($totalTransferFees, 2) . "\n";
echo "Total Costs: ₦" . number_format($totalCosts, 2) . "\n";
echo "NET PROFIT: ₦" . number_format($netProfit, 2) . "\n";

if ($netProfit > 0) {
    echo "✅ YOU ARE MAKING PROFIT!\n";
} else {
    echo "❌ YOU ARE MAKING A LOSS!\n";
}
