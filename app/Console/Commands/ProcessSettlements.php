<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyWallet;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessSettlements extends Command
{
    protected $signature = 'settlements:process';
    protected $description = 'Process pending settlements based on configured rules';

    public function handle()
    {
        $this->info('Starting settlement processing...');

        try {
            // Get global settings
            $settings = DB::table('settings')->first();
            
            if (!$settings || !$settings->auto_settlement_enabled) {
                $this->info('Auto settlement is disabled');
                return 0;
            }

            $now = now();
            
            // Get pending settlements that are due
            $pendingSettlements = DB::table('settlement_queue')
                ->where('status', 'pending')
                ->where('scheduled_settlement_date', '<=', $now)
                ->orderBy('scheduled_settlement_date')
                ->get();

            if ($pendingSettlements->isEmpty()) {
                $this->info('No pending settlements to process');
                return 0;
            }

            $this->info("Found {$pendingSettlements->count()} settlements to process");

            $processed = 0;
            $failed = 0;

            foreach ($pendingSettlements as $settlement) {
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

                    // Settlement is just releasing held funds - NO FEE
                    // The customer already paid fees when they deposited
                    $netAmount = $settlement->amount;

                    // Get company wallet
                    $wallet = CompanyWallet::where('company_id', $settlement->company_id)
                        ->where('currency', 'NGN')
                        ->lockForUpdate()
                        ->first();

                    if (!$wallet) {
                        throw new \Exception("Wallet not found for company: {$settlement->company_id}");
                    }

                    // Credit the wallet with full amount (no fee deduction)
                    $balanceBefore = $wallet->balance;
                    $wallet->credit($netAmount);
                    $wallet->save();

                    // Update transaction with settlement info
                    $transaction->update([
                        'balance_before' => $balanceBefore,
                        'balance_after' => $wallet->balance,
                        'metadata' => array_merge($transaction->metadata ?? [], [
                            'settled_at' => $now->toDateTimeString(),
                            'settlement_delay_hours' => $this->calculateDelayHours($settlement->transaction_date, $now),
                        ]),
                    ]);

                    // Mark settlement as completed
                    DB::table('settlement_queue')
                        ->where('id', $settlement->id)
                        ->update([
                            'status' => 'completed',
                            'actual_settlement_date' => $now,
                            'settlement_note' => "Settled successfully. Amount: {$netAmount} NGN",
                        ]);

                    DB::commit();

                    $this->info("✓ Settled: {$netAmount} NGN for company {$settlement->company_id}");
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
                    Log::error('Settlement Processing Failed', [
                        'settlement_id' => $settlement->id,
                        'error' => $e->getMessage(),
                    ]);

                    $failed++;
                }
            }

            $this->info("\nSettlement Summary:");
            $this->info("Processed: {$processed}");
            $this->info("Failed: {$failed}");

            return 0;

        } catch (\Exception $e) {
            $this->error("Settlement processing error: {$e->getMessage()}");
            Log::error('Settlement Command Failed', ['error' => $e->getMessage()]);
            return 1;
        }
    }

    /**
     * Calculate delay hours between two dates
     */
    private function calculateDelayHours(string $from, Carbon $to): int
    {
        $fromDate = Carbon::parse($from);
        return (int) $fromDate->diffInHours($to);
    }

    /**
     * Calculate settlement date based on delay hours
     * Skips weekends and holidays based on settings
     * 
     * IMPORTANT: settlementTime is ONLY used when delay is 24+ hours (full days)
     * For delays under 24 hours, the exact time is preserved
     */
    public static function calculateSettlementDate(
        Carbon $transactionDate,
        int $delayHours,
        bool $skipWeekends = true,
        bool $skipHolidays = true,
        string $settlementTime = '02:00:00'
    ): Carbon {
        // Add delay hours
        $settlementDate = $transactionDate->copy()->addHours($delayHours);

        // If skip weekends is enabled
        if ($skipWeekends) {
            // If settlement falls on weekend, move to next Monday
            while ($settlementDate->isWeekend()) {
                $settlementDate->addDay();
            }
        }

        // Only set specific settlement time if delay is 24+ hours (full day settlement)
        // For shorter delays (minutes/hours), preserve the exact calculated time
        if ($delayHours >= 24) {
            list($hour, $minute, $second) = explode(':', $settlementTime);
            $settlementDate->setTime((int)$hour, (int)$minute, (int)$second);
        }

        // If skip holidays is enabled (you can add holiday checking logic here)
        if ($skipHolidays) {
            // TODO: Check against holidays table if needed
            // For now, we just handle weekends
        }

        return $settlementDate;
    }
}
