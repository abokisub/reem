// === COMPLETE PROFIT/LOSS ANALYSIS (ALL TIME) ===
// Run this in: php artisan tinker
// Analyzes ALL transactions including VA charges, transfers, and all costs

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║     COMPLETE PROFIT/LOSS ANALYSIS - ALL TRANSACTIONS      ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Get ALL successful transactions
$allTxns = DB::table('transactions')
    ->where('status', 'success')
    ->orderBy('created_at', 'asc')
    ->get();

echo "📊 Total Transactions: " . $allTxns->count() . "\n";
echo "📅 First Transaction: " . ($allTxns->first()->created_at ?? 'N/A') . "\n";
echo "📅 Last Transaction: " . ($allTxns->last()->created_at ?? 'N/A') . "\n";
echo "\n" . str_repeat("─", 60) . "\n\n";

// Initialize counters
$totalRevenue = 0;
$totalPalmPayFees = 0;
$totalTransferFees = 0;
$totalVACharges = 0;
$totalAmount = 0;
$profitableTxns = 0;
$lossTxns = 0;

echo "💰 TRANSACTION BREAKDOWN:\n\n";

foreach ($allTxns as $txn) {
    $amount = (float) $txn->amount;
    $yourFee = (float) ($txn->fee ?? 0);
    
    // Calculate PalmPay VA charge (0.5% capped at ₦500)
    $palmpayFee = min(($amount * 0.005), 500);
    
    // Transfer fee (₦25 per transaction)
    $transferFee = 25;
    
    // Total cost
    $totalCost = $palmpayFee + $transferFee;
    
    // Profit/Loss per transaction
    $profit = $yourFee - $totalCost;
    
    // Accumulate totals
    $totalAmount += $amount;
    $totalRevenue += $yourFee;
    $totalPalmPayFees += $palmpayFee;
    $totalTransferFees += $transferFee;
    
    if ($profit >= 0) {
        $profitableTxns++;
    } else {
        $lossTxns++;
    }
}

$totalCosts = $totalPalmPayFees + $totalTransferFees;
$netProfitLoss = $totalRevenue - $totalCosts;
$profitMargin = $totalRevenue > 0 ? (($netProfitLoss / $totalRevenue) * 100) : 0;
$avgTxnAmount = $allTxns->count() > 0 ? ($totalAmount / $allTxns->count()) : 0;
$avgRevenue = $allTxns->count() > 0 ? ($totalRevenue / $allTxns->count()) : 0;
$avgCost = $allTxns->count() > 0 ? ($totalCosts / $allTxns->count()) : 0;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║                      REVENUE SUMMARY                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo sprintf("💵 Total Transaction Volume:     ₦%s\n", number_format($totalAmount, 2));
echo sprintf("💰 Your Total Revenue (Fees):    ₦%s\n", number_format($totalRevenue, 2));
echo sprintf("📊 Average Fee per Transaction:  ₦%s\n", number_format($avgRevenue, 2));
echo sprintf("📈 Average Fee Percentage:       %.3f%%\n", $avgTxnAmount > 0 ? (($avgRevenue / $avgTxnAmount) * 100) : 0);

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║                       COST BREAKDOWN                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo sprintf("🏦 PalmPay VA Charges (0.5%%):    ₦%s\n", number_format($totalPalmPayFees, 2));
echo sprintf("💸 Transfer Fees (₦25 each):     ₦%s\n", number_format($totalTransferFees, 2));
echo sprintf("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n");
echo sprintf("💰 TOTAL COSTS:                  ₦%s\n", number_format($totalCosts, 2));
echo sprintf("📊 Average Cost per Transaction: ₦%s\n", number_format($avgCost, 2));

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║                    PROFIT/LOSS SUMMARY                     ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

if ($netProfitLoss > 0) {
    echo "✅ STATUS: PROFITABLE\n\n";
    echo sprintf("💚 NET PROFIT:                   ₦%s\n", number_format($netProfitLoss, 2));
    echo sprintf("📈 Profit Margin:                %.2f%%\n", $profitMargin);
    echo sprintf("✅ Profitable Transactions:      %d (%.1f%%)\n", $profitableTxns, ($profitableTxns / $allTxns->count()) * 100);
    echo sprintf("❌ Loss-Making Transactions:     %d (%.1f%%)\n", $lossTxns, ($lossTxns / $allTxns->count()) * 100);
} elseif ($netProfitLoss < 0) {
    echo "❌ STATUS: MAKING A LOSS\n\n";
    echo sprintf("💔 NET LOSS:                     ₦%s\n", number_format(abs($netProfitLoss), 2));
    echo sprintf("📉 Loss Margin:                  %.2f%%\n", abs($profitMargin));
    echo sprintf("✅ Profitable Transactions:      %d (%.1f%%)\n", $profitableTxns, ($profitableTxns / $allTxns->count()) * 100);
    echo sprintf("❌ Loss-Making Transactions:     %d (%.1f%%)\n", $lossTxns, ($lossTxns / $allTxns->count()) * 100);
} else {
    echo "⚖️  STATUS: BREAK EVEN\n\n";
    echo "You're neither making profit nor loss.\n";
}

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║                      RECOMMENDATIONS                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Calculate recommended fees
$avgPalmpayFee = min(($avgTxnAmount * 0.005), 500);
$avgTotalCost = $avgPalmpayFee + 25;

// Break-even fee
$breakEvenFee = $avgTotalCost;
$breakEvenPercent = ($breakEvenFee / $avgTxnAmount) * 100;

// 30% profit margin
$profit30Fee = $avgTotalCost * 1.3;
$profit30Percent = ($profit30Fee / $avgTxnAmount) * 100;

// 50% profit margin
$profit50Fee = $avgTotalCost * 1.5;
$profit50Percent = ($profit50Fee / $avgTxnAmount) * 100;

echo "📊 Average Transaction Amount: ₦" . number_format($avgTxnAmount, 2) . "\n";
echo "💰 Average Cost per Transaction: ₦" . number_format($avgTotalCost, 2) . "\n";
echo "💵 Current Average Fee: ₦" . number_format($avgRevenue, 2) . "\n\n";

echo "🎯 RECOMMENDED FEE STRUCTURES:\n\n";

echo "1️⃣  BREAK EVEN (No Profit, No Loss):\n";
echo sprintf("   Fee: ₦%.2f per transaction\n", $breakEvenFee);
echo sprintf("   Percentage: %.3f%%\n\n", $breakEvenPercent);

echo "2️⃣  HEALTHY PROFIT (30%% margin):\n";
echo sprintf("   Fee: ₦%.2f per transaction\n", $profit30Fee);
echo sprintf("   Percentage: %.3f%%\n\n", $profit30Percent);

echo "3️⃣  STRONG PROFIT (50%% margin):\n";
echo sprintf("   Fee: ₦%.2f per transaction\n", $profit50Fee);
echo sprintf("   Percentage: %.3f%%\n\n", $profit50Percent);

echo "💡 SUGGESTED ACTION:\n";
if ($netProfitLoss < 0) {
    echo "   ⚠️  You are currently losing money!\n";
    echo "   ⚠️  Immediately update your fees to at least the break-even rate.\n";
    echo "   ⚠️  Recommended: Set fees to " . number_format($profit30Percent, 2) . "% for healthy profit.\n";
} elseif ($profitMargin < 20) {
    echo "   ⚠️  Your profit margin is too low (below 20%).\n";
    echo "   💡 Consider increasing fees to " . number_format($profit30Percent, 2) . "% for better margins.\n";
} else {
    echo "   ✅ Your current fee structure is working well!\n";
    echo "   💡 Monitor regularly to ensure continued profitability.\n";
}

echo "\n" . str_repeat("═", 60) . "\n";
echo "Analysis Complete! " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("═", 60) . "\n\n";
