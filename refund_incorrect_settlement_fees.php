<?php
/**
 * Refund Incorrect Settlement Fees
 * 
 * Settlements #4 and #5 were processed with incorrect ₦15 withdrawal fee deduction
 * This script refunds ₦30 total (2 × ₦15) to company ID 2 (PointWave Business)
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use App\Models\Transaction;
use App\Models\SettlementQueue;
use Illuminate\Support\Facades\DB;

echo "=== REFUND INCORRECT SETTLEMENT FEES ===\n\n";

try {
    DB::beginTransaction();
    
    // Get company
    $company = Company::find(2);
    if (!$company) {
        throw new Exception("Company ID 2 not found");
    }
    
    echo "Company: {$company->name}\n";
    echo "Current Available Balance: ₦" . number_format($company->available_balance, 2) . "\n\n";
    
    // Get the 2 settlements that were incorrectly charged
    $settlements = SettlementQueue::whereIn('id', [4, 5])
        ->where('company_id', 2)
        ->where('status', 'completed')
        ->get();
    
    if ($settlements->count() !== 2) {
        throw new Exception("Expected 2 settlements, found " . $settlements->count());
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "SETTLEMENTS TO REFUND:\n\n";
    
    $totalRefund = 0;
    foreach ($settlements as $settlement) {
        $transaction = Transaction::find($settlement->transaction_id);
        echo "Settlement ID: {$settlement->id}\n";
        echo "Transaction ID: {$settlement->transaction_id}\n";
        echo "Amount Settled: ₦" . number_format($settlement->amount, 2) . "\n";
        echo "Settled At: {$settlement->settled_at}\n";
        echo "Incorrect Fee Charged: ₦15.00\n\n";
        $totalRefund += 15;
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "TOTAL REFUND: ₦" . number_format($totalRefund, 2) . "\n\n";
    
    // Credit the company's available balance
    $oldBalance = $company->available_balance;
    $company->available_balance += $totalRefund;
    $company->save();
    
    $newBalance = $company->available_balance;
    
    echo "✅ REFUND PROCESSED\n\n";
    echo "Previous Balance: ₦" . number_format($oldBalance, 2) . "\n";
    echo "Refund Amount: ₦" . number_format($totalRefund, 2) . "\n";
    echo "New Balance: ₦" . number_format($newBalance, 2) . "\n\n";
    
    // Create a transaction record for the refund
    $refundTransaction = new Transaction();
    $refundTransaction->company_id = $company->id;
    $refundTransaction->type = 'credit';
    $refundTransaction->amount = $totalRefund;
    $refundTransaction->fee = 0;
    $refundTransaction->net_amount = $totalRefund;
    $refundTransaction->status = 'successful';
    $refundTransaction->reference = 'REFUND-SETTLEMENT-FEE-' . time();
    $refundTransaction->session_id = 'REFUND-' . uniqid();
    $refundTransaction->description = 'Refund of incorrect settlement withdrawal fees (Settlements #4 and #5)';
    $refundTransaction->transaction_type = 'refund';
    $refundTransaction->save();
    
    echo "📝 Transaction Record Created:\n";
    echo "Transaction ID: {$refundTransaction->id}\n";
    echo "Reference: {$refundTransaction->reference}\n";
    echo "Session ID: {$refundTransaction->session_id}\n\n";
    
    DB::commit();
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ REFUND COMPLETE!\n\n";
    echo "Summary:\n";
    echo "- Refunded ₦30.00 to {$company->name}\n";
    echo "- New available balance: ₦" . number_format($newBalance, 2) . "\n";
    echo "- Transaction record created for audit trail\n";
    
} catch (Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
