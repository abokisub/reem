<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== PENDING SETTLEMENTS DETAILS ===\n\n";

$pendingSettlements = DB::table('settlement_queue')
    ->join('companies', 'settlement_queue.company_id', '=', 'companies.id')
    ->join('settings', 'companies.id', '=', 'settings.company_id')
    ->where('settlement_queue.status', 'pending')
    ->select(
        'settlement_queue.*',
        'companies.name as company_name',
        'companies.custom_settlement_enabled',
        'companies.custom_settlement_delay_hours',
        'settings.settlement_delay_hours'
    )
    ->orderBy('settlement_queue.created_at', 'asc')
    ->get();

if ($pendingSettlements->isEmpty()) {
    echo "No pending settlements.\n";
    exit(0);
}

foreach ($pendingSettlements as $settlement) {
    $createdAt = Carbon::parse($settlement->created_at);
    $scheduledAt = Carbon::parse($settlement->scheduled_settlement_date);
    $now = Carbon::now();
    
    // Calculate delay
    $delayHours = $settlement->custom_settlement_enabled && $settlement->custom_settlement_delay_hours !== null
        ? floatval($settlement->custom_settlement_delay_hours)
        : floatval($settlement->settlement_delay_hours);
    
    $delayMinutes = $delayHours * 60;
    $eligibleAt = $createdAt->copy()->addMinutes($delayMinutes);
    
    $isEligible = $now->gte($eligibleAt);
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Settlement ID: {$settlement->id}\n";
    echo "Company: {$settlement->company_name}\n";
    echo "Amount: ₦" . number_format($settlement->amount, 2) . "\n";
    echo "Transaction ID: {$settlement->transaction_id}\n";
    echo "Status: {$settlement->status}\n";
    echo "\n";
    echo "⏰ TIMING:\n";
    echo "  Created: {$createdAt->format('Y-m-d H:i:s')} ({$createdAt->diffForHumans()})\n";
    echo "  Scheduled For: {$scheduledAt->format('Y-m-d H:i:s')}\n";
    echo "  Delay Setting: {$delayHours} hours ({$delayMinutes} minutes)\n";
    echo "  Should be eligible at: {$eligibleAt->format('Y-m-d H:i:s')}\n";
    echo "  Current Time: {$now->format('Y-m-d H:i:s')}\n";
    echo "\n";
    
    if ($isEligible) {
        $waitedMinutes = $now->diffInMinutes($createdAt);
        echo "✅ ELIGIBLE FOR SETTLEMENT (waited {$waitedMinutes} minutes)\n";
        echo "   This should have been settled already!\n";
    } else {
        $remainingMinutes = $eligibleAt->diffInMinutes($now);
        echo "⏳ NOT YET ELIGIBLE (need to wait {$remainingMinutes} more minutes)\n";
    }
    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Check if cron is set up
echo "🔍 CRON JOB CHECK:\n";
echo "The settlement processor should run every minute via cron.\n";
echo "Expected crontab entry:\n";
echo "  * * * * * cd " . base_path() . " && php artisan schedule:run >> /dev/null 2>&1\n\n";

echo "To check if cron is running, look for recent Laravel scheduler logs.\n";
echo "To manually process settlements NOW, run:\n";
echo "  php artisan settlements:process\n\n";
