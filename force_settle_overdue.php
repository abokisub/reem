<?php

/**
 * Force Settle Overdue Settlements
 * 
 * This script manually processes overdue settlements
 * Use this when the cron job is not running or settlements are stuck
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\CompanyWallet;
use App\Models\Transaction;

echo "========================================\n";
echo "Force Settle Overdue Settlements\n";
echo "========================================\n\n";

// Get current time
$now = Carbon::now();
echo "Current Time: " . $now->format('Y-m-d H:i:s') . "\n\n";

// Get all overdue pending settlements
$overdueSettlements = DB::table('settlement_queue')
    ->where('status', 'pending')
    ->where('scheduled_settlement_date', '<=', $now)
    ->orderBy('scheduled_settlement_date')
    ->get();

if ($overdueSettlements->isEmpty()) {
    echo "No overdue settlements found.\n";
    exit;
}

echo "Found {$overdueSettlements->count()} overdue settlements\n\n";

$processed = 0;
$failed = 0;
$errors = [];

foreach ($overdueSettlements as $settlement) {
    echo "----------------------------------------\n";
    echo "Processing Settlement ID: {$settlement->id}\n";
    echo "Company ID: {$settlement->company_id}\n";
    echo "Amount: ₦" . number_format($settlement->amount, 2) . "\n";
    
    try {
        DB::beginTransaction();
        
        // Mark as processing
        DB::table('settlement_queue')
            ->where('id', $settlement->id)
            ->update(['status' => 'processing']);
        
        // Get transaction
        $transaction = Transaction::find($settlement->transaction_id);
        
        if (!$transaction) {
            throw new \Exception("Transaction not found: {$settlement->transaction_id}");
        }
        
        echo "Transaction Reference: {$transaction->reference}\n";
        
        // Settlement is just releasing held funds - NO FEE
        $netAmount = $settlement->amount;
        
        // Get company wallet with lock
        $wallet = CompanyWallet::where('company_id', $settlement->company_id)
            ->where('currency', 'NGN')
            ->lockForUpdate()
            ->first();
        
        if (!$wallet) {
            throw new \Exception("Wallet not found for company: {$settlement->company_id}");
        }
        
        echo "Wallet Balance Before: ₦" . number_format($wallet->balance, 2) . "\n";
        
        // Credit the wallet with full amount
        $balanceBefore = $wallet->balance;
        $wallet->credit($netAmount);
        $wallet->save();
        
        echo "Wallet Balance After: ₦" . number_format($wallet->balance, 2) . "\n";
        
        // Update transaction with settlement info
        $transaction->update([
            'balance_before' => $balanceBefore,
            'balance_after' => $wallet->balance,
            'metadata' => array_merge($transaction->metadata ?? [], [
                'settled_at' => $now->toDateTimeString(),
                'settlement_delay_hours' => Carbon::parse($settlement->transaction_date)->diffInHours($now),
                'manually_settled' => true,
            ]),
        ]);
        
        // Mark settlement as completed
        DB::table('settlement_queue')
            ->where('id', $settlement->id)
            ->update([
                'status' => 'completed',
                'actual_settlement_date' => $now,
                'settlement_note' => "Manually settled. Amount: {$netAmount} NGN",
            ]);
        
        // Send success email
        try {
            $company = Company::find($settlement->company_id);
            if ($company && $company->email) {
                $email_data = [
                    'company_name' => $company->name,
                    'email' => $company->email,
                    'amount' => $netAmount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $wallet->balance,
                    'reference' => $transaction->transaction_id ?? $transaction->reference,
                    'settlement_date' => $now->format('d M Y, h:i A'),
                    'title' => 'Settlement Successful',
                    'sender_mail' => config('mail.from.address'),
                    'app_name' => config('app.name'),
                ];
                \App\Http\Controllers\MailController::send_mail($email_data, 'email.settlement_success');
                echo "Email sent to: {$company->email}\n";
            }
        } catch (\Throwable $e) {
            echo "Email Error: " . $e->getMessage() . "\n";
        }
        
        DB::commit();
        
        echo "✅ SUCCESS\n";
        $processed++;
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        // Mark as failed
        DB::table('settlement_queue')
            ->where('id', $settlement->id)
            ->update([
                'status' => 'failed',
                'settlement_note' => $e->getMessage(),
            ]);
        
        echo "❌ FAILED: " . $e->getMessage() . "\n";
        $errors[] = [
            'settlement_id' => $settlement->id,
            'company_id' => $settlement->company_id,
            'error' => $e->getMessage()
        ];
        $failed++;
    }
}

echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Processed: {$processed}\n";
echo "Failed: {$failed}\n";

if (!empty($errors)) {
    echo "\nERRORS:\n";
    foreach ($errors as $error) {
        echo "- Settlement {$error['settlement_id']} (Company {$error['company_id']}): {$error['error']}\n";
    }
}

echo "========================================\n";
