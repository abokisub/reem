<?php

/**
 * Check Stuck Settlements
 * 
 * This script finds settlements that are overdue and should have been processed
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "========================================\n";
echo "Check Stuck Settlements\n";
echo "========================================\n\n";

// Get current time
$now = Carbon::now();
echo "Current Time: " . $now->format('Y-m-d H:i:s') . "\n\n";

// Check if cron is enabled
$settings = DB::table('settings')->first();
echo "Auto Settlement Enabled: " . ($settings->auto_settlement_enabled ?? 'NOT SET') . "\n\n";

// Get all pending settlements
$pendingSettlements = DB::table('settlement_queue')
    ->where('status', 'pending')
    ->orderBy('scheduled_settlement_date')
    ->get();

echo "Total Pending Settlements: " . $pendingSettlements->count() . "\n\n";

if ($pendingSettlements->isEmpty()) {
    echo "No pending settlements found.\n";
    exit;
}

echo "========================================\n";
echo "OVERDUE SETTLEMENTS\n";
echo "========================================\n\n";

$overdueCount = 0;

foreach ($pendingSettlements as $settlement) {
    $scheduledDate = Carbon::parse($settlement->scheduled_settlement_date);
    $isOverdue = $scheduledDate->isPast();
    $hoursOverdue = $now->diffInHours($scheduledDate, false);
    
    if ($isOverdue) {
        $overdueCount++;
        
        echo "Settlement ID: {$settlement->id}\n";
        echo "Company ID: {$settlement->company_id}\n";
        echo "Amount: ₦" . number_format($settlement->amount, 2) . "\n";
        echo "Transaction ID: {$settlement->transaction_id}\n";
        echo "Transaction Date: {$settlement->transaction_date}\n";
        echo "Scheduled Date: {$settlement->scheduled_settlement_date}\n";
        echo "Hours Overdue: " . abs($hoursOverdue) . " hours\n";
        echo "Status: {$settlement->status}\n";
        
        // Get company info
        $company = DB::table('companies')->where('id', $settlement->company_id)->first();
        if ($company) {
            echo "Company Name: {$company->name}\n";
            echo "Company Email: {$company->email}\n";
        }
        
        // Get transaction info
        $transaction = DB::table('transactions')->where('id', $settlement->transaction_id)->first();
        if ($transaction) {
            echo "Transaction Reference: {$transaction->reference}\n";
            echo "Transaction Status: {$transaction->status}\n";
            echo "Transaction Type: {$transaction->transaction_type}\n";
        }
        
        echo "----------------------------------------\n\n";
    }
}

echo "========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Total Pending: {$pendingSettlements->count()}\n";
echo "Overdue: {$overdueCount}\n";
echo "========================================\n";
