<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== SETTLEMENT HISTORY CHECK ===\n\n";

// Get recent completed settlements
$completedSettlements = DB::table('settlement_queue')
    ->join('companies', 'settlement_queue.company_id', '=', 'companies.id')
    ->where('settlement_queue.status', 'completed')
    ->select(
        'settlement_queue.*',
        'companies.name as company_name'
    )
    ->orderBy('settlement_queue.actual_settlement_date', 'desc')
    ->limit(10)
    ->get();

if ($completedSettlements->isEmpty()) {
    echo "❌ No completed settlements found.\n";
    echo "This might indicate settlements are not being processed.\n\n";
} else {
    echo "✅ Found " . $completedSettlements->count() . " recent completed settlement(s):\n\n";
    
    foreach ($completedSettlements as $settlement) {
        $transactionDate = Carbon::parse($settlement->transaction_date);
        $scheduledDate = Carbon::parse($settlement->scheduled_settlement_date);
        $actualDate = Carbon::parse($settlement->actual_settlement_date);
        
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "Settlement ID: {$settlement->id}\n";
        echo "Company: {$settlement->company_name}\n";
        echo "Amount: ₦" . number_format($settlement->amount, 2) . "\n";
        echo "Transaction ID: {$settlement->transaction_id}\n";
        echo "Status: {$settlement->status}\n";
        echo "\n";
        echo "⏰ TIMELINE:\n";
        echo "  Transaction Date: {$transactionDate->format('Y-m-d H:i:s')}\n";
        echo "  Scheduled For: {$scheduledDate->format('Y-m-d H:i:s')}\n";
        echo "  Actually Settled: {$actualDate->format('Y-m-d H:i:s')}\n";
        echo "  Processing Time: " . $transactionDate->diffForHumans($actualDate, true) . "\n";
        echo "\n";
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
}

// Check all settlement statuses
$statusCounts = DB::table('settlement_queue')
    ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total_amount'))
    ->groupBy('status')
    ->get();

echo "📊 SETTLEMENT STATUS SUMMARY:\n";
foreach ($statusCounts as $status) {
    echo "  {$status->status}: {$status->count} settlements (₦" . number_format($status->total_amount, 2) . ")\n";
}
echo "\n";

// Check recent deposits
$recentDeposits = DB::table('transactions')
    ->where('type', 'credit')
    ->where('category', 'deposit')
    ->where('status', 'success')
    ->where('created_at', '>=', Carbon::now()->subHours(24))
    ->count();

echo "💰 RECENT ACTIVITY (Last 24 hours):\n";
echo "  Successful Deposits: {$recentDeposits}\n\n";

// Check if cron is running
$lastSettlement = DB::table('settlement_queue')
    ->where('status', 'completed')
    ->orderBy('actual_settlement_date', 'desc')
    ->first();

if ($lastSettlement) {
    $lastSettlementTime = Carbon::parse($lastSettlement->actual_settlement_date);
    $minutesSinceLastSettlement = $lastSettlementTime->diffInMinutes(Carbon::now());
    
    echo "🔄 SYSTEM STATUS:\n";
    echo "  Last Settlement: {$lastSettlementTime->format('Y-m-d H:i:s')} ({$lastSettlementTime->diffForHumans()})\n";
    
    if ($minutesSinceLastSettlement > 60 && $recentDeposits > 0) {
        echo "  ⚠️  WARNING: Recent deposits but no settlements in over 1 hour\n";
        echo "  Check if cron job is running: * * * * * cd /path/to/app && php artisan schedule:run\n";
    } else {
        echo "  ✅ Settlement system appears to be working normally\n";
    }
}

echo "\n";
