<?php

namespace App\Console\Commands;

use App\Services\TransactionReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Process Transaction Reconciliation Command
 * 
 * Runs scheduled reconciliation for stale transactions that are stuck in 
 * 'processing' or 'pending' state. Queries payment provider for current status
 * and updates transactions accordingly.
 * 
 * This command is designed to be idempotent and safe to run multiple times.
 * It catches transactions where webhooks were missed or failed.
 * 
 * Scheduled to run every 5 minutes via cron.
 * 
 * @package App\Console\Commands
 */
class ProcessTransactionReconciliation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:reconcile';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcile stale transactions with payment provider status';

    /**
     * Transaction Reconciliation Service
     *
     * @var TransactionReconciliationService
     */
    protected $reconciliationService;

    /**
     * Create a new command instance.
     *
     * @param TransactionReconciliationService $reconciliationService
     * @return void
     */
    public function __construct(TransactionReconciliationService $reconciliationService)
    {
        parent::__construct();
        $this->reconciliationService = $reconciliationService;
    }

    /**
     * Execute the console command.
     * 
     * Calls TransactionReconciliationService->runScheduledReconciliation()
     * and logs the results. Handles exceptions gracefully to prevent cron failures.
     *
     * @return int Command exit code (0 = success, 1 = failure)
     */
    public function handle()
    {
        $startTime = now();
        $this->info('Starting transaction reconciliation...');
        
        Log::info('Transaction reconciliation command started', [
            'started_at' => $startTime->toDateTimeString()
        ]);

        try {
            // Run scheduled reconciliation
            $reconciledCount = $this->reconciliationService->runScheduledReconciliation();

            $endTime = now();
            $duration = $startTime->diffInSeconds($endTime);

            // Log results
            $this->info("Reconciliation completed successfully");
            $this->info("Reconciled transactions: {$reconciledCount}");
            $this->info("Duration: {$duration} seconds");

            Log::info('Transaction reconciliation command completed', [
                'reconciled_count' => $reconciledCount,
                'started_at' => $startTime->toDateTimeString(),
                'completed_at' => $endTime->toDateTimeString(),
                'duration_seconds' => $duration
            ]);

            return 0;

        } catch (\Exception $e) {
            $endTime = now();
            $duration = $startTime->diffInSeconds($endTime);

            // Log error
            $this->error("Reconciliation failed: {$e->getMessage()}");
            
            Log::error('Transaction reconciliation command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'started_at' => $startTime->toDateTimeString(),
                'failed_at' => $endTime->toDateTimeString(),
                'duration_seconds' => $duration
            ]);

            return 1;
        }
    }
}

