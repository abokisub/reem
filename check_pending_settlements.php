<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== PENDING SETTLEMENTS CHECK ===\n\n";

// Get all pending settlements with company info
$pendingSettlements = DB::table('settlement_queue')
    ->join('companies', 'settlement_queue.company_id', '=', 'companies.id')
    ->join('settings', 'companies.id', '=', 'settings.company_id')
    ->where('settlement_queue.status', 'pending')
    ->select(
        'settlement_queue.*',
        'companies.name as company_name',
        'settings.settlement_delay'
    )
    ->orderBy('settlement_queue.created_at', 'asc')
    ->get();

if ($pendingSettlements->isEmpty()) {
    echo "✅ No pending settlements found.\n";
    exit(0);
}

echo "Found " . $pendingSettlements->count() . " pending settlement(s):\n\n";

foreach ($pendingSettlements as $settlement) {
    $createdAt = Carbon::parse($settlement->created_at);
    $now = Carbon::now();
    $delayMinutes = floatval($settlement->settlement_delay);
    $eligibleAt = $createdAt->copy()->addMinutes($delayMinutes);
    
    $minutesWaited = $now->diffInMinutes($createdAt, false);
    $minutesRemaining = $eligibleAt->diffInMinutes($now, false);
    
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
    echo "  Delay Setting: {$delayMinutes} minutes\n";
    echo "  Eligible At: {$eligibleAt->format('Y-m-d H:i:s')} ({$eligibleAt->diffForHumans()})\n";
    echo "  Current Time: {$now->format('Y-m-d H:i:s')}\n";
    echo "\n";
    
    if ($isEligible) {
        echo "✅ STATUS: READY FOR SETTLEMENT (waited {$minutesWaited} minutes)\n";
        echo "   This settlement can be processed NOW!\n";
    } else {
        echo "⏳ STATUS: WAITING (waited {$minutesWaited} minutes)\n";
        echo "   Time remaining: {$minutesRemaining} minutes\n";
        echo "   Will be eligible in: " . $eligibleAt->diffForHumans() . "\n";
    }
    echo "\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Summary
$readyCount = $pendingSettlements->filter(function($s) {
    $createdAt = Carbon::parse($s->created_at);
    $delayMinutes = floatval($s->settlement_delay);
    $eligibleAt = $createdAt->copy()->addMinutes($delayMinutes);
    return Carbon::now()->gte($eligibleAt);
})->count();

$waitingCount = $pendingSettlements->count() - $readyCount;

echo "📊 SUMMARY:\n";
echo "  Total Pending: {$pendingSettlements->count()}\n";
echo "  Ready Now: {$readyCount}\n";
echo "  Still Waiting: {$waitingCount}\n\n";

if ($readyCount > 0) {
    echo "💡 To process ready settlements, run:\n";
    echo "   php artisan settlements:process\n\n";
}

echo "🔄 Settlement processing runs automatically every minute via cron.\n";
echo "   Check crontab: * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1\n\n";
