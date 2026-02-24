<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyWallet;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessOverdueSettlements extends Command
{
    protected $signature = 'settlements:process-overdue';
    protected $description = 'Process overdue settlements (fallback for missed cron runs)';

    public function handle()
    {
        $this->info('Checking for overdue settlements...');

        try {
            $now = now();
            
            // Get overdue pending settlements
            $overdueSettlements = DB::table('settlement_queue')
                ->where('status', 'pending')
                ->where('scheduled_settlement_date', '<=', $now)
                ->orderBy('scheduled_settlement_date')
                ->get();

            if ($overdueSettlements->isEmpty()) {
                $this->info('No overdue settlements found');
                return 0;
            }

            $this->warn("Found {$overdueSettlements->count()} overdue settlements!");

            $processed = 0;
            $failed = 0;

            foreach ($overdueSettlements as $settlement) {
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
                    
                    // Get company wallet with lock
                    $wallet = CompanyWallet::where('company_id', $settlement->company_id)
                        ->where('currency', 'NGN')
                        ->lockForUpdate()
                        ->first();
                    
                    if (!$wallet) {
                        throw new \Exception("Wallet not found for company: {$settlement->company_id}");
                    }
                    
                    // Credit the wallet
                    $balanceBefore = $wallet->balance;
                    $wallet->credit($settlement->amount);
                    $wallet->save();
                    
                    // Update transaction
                    $transaction->update([
                        'settlement_status' => 'settled',
                        'balance_before' => $balanceBefore,
                        'balance_after' => $wallet->balance,
                        'metadata' => array_merge($transaction->metadata ?? [], [
                            'settled_at' => $now->toDateTimeString(),
                            'settlement_type' => 'overdue_fallback',
                        ]),
                    ]);
                    
                    // Mark settlement as completed
                    DB::table('settlement_queue')
                        ->where('id', $settlement->id)
                        ->update([
                            'status' => 'completed',
                            'actual_settlement_date' => $now,
                            'settlement_note' => "Processed by overdue fallback command",
                        ]);
                    
                    // Send success email
                    try {
                        $company = Company::find($settlement->company_id);
                        if ($company && $company->email) {
                            $email_data = [
                                'company_name' => $company->name,
                                'email' => $company->email,
                                'amount' => $settlement->amount,
                                'balance_before' => $balanceBefore,
                                'balance_after' => $wallet->balance,
                                'reference' => $transaction->transaction_id ?? $transaction->reference,
                                'settlement_date' => $now->format('d M Y, h:i A'),
                                'title' => 'Settlement Successful',
                                'sender_mail' => config('mail.from.address'),
                                'app_name' => config('app.name'),
                            ];
                            \App\Http\Controllers\MailController::send_mail($email_data, 'email.settlement_success');
                        }
                    } catch (\Throwable $e) {
                        Log::error('Settlement Success Email Error: ' . $e->getMessage());
                    }
                    
                    DB::commit();
                    
                    $this->info("✓ Settled: {$settlement->amount} NGN for company {$settlement->company_id}");
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
                    
                    $this->error("✗ Failed: {$e->getMessage()}");
                    Log::error('Overdue Settlement Processing Failed', [
                        'settlement_id' => $settlement->id,
                        'error' => $e->getMessage(),
                    ]);
                    
                    $failed++;
                }
            }

            $this->info("\nOverdue Settlement Summary:");
            $this->info("Processed: {$processed}");
            $this->info("Failed: {$failed}");

            return 0;

        } catch (\Exception $e) {
            $this->error("Overdue settlement processing error: {$e->getMessage()}");
            Log::error('Overdue Settlement Command Failed', ['error' => $e->getMessage()]);
            return 1;
        }
    }
}
