<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\VirtualAccount;
use App\Services\PalmPay\VirtualAccountService;
use Illuminate\Support\Facades\Log;

class DisableAllVirtualAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'virtual-accounts:disable-all {--force : Force operation without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disables all virtual accounts across all companies and deletes them from PalmPay';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(VirtualAccountService $virtualAccountService)
    {
        if (!$this->option('force') && !$this->confirm('Are you absolutely sure you want to disable ALL virtual accounts? This action will block all incoming deposits and cannot be easily undone.')) {
            $this->info('Operation cancelled.');
            return 0;
        }

        $this->info('Starting mass deactivation of Virtual Accounts...');

        $totalAccounts = VirtualAccount::where('status', 'active')->count();
        $this->info("Found {$totalAccounts} active virtual accounts to disable.");

        if ($totalAccounts === 0) {
            $this->info('No active virtual accounts found. Exiting.');
            return 0;
        }

        $bar = $this->output->createProgressBar($totalAccounts);
        $bar->start();

        $successCount = 0;
        $failCount = 0;

        // Process in chunks to avoid memory exhaustion
        VirtualAccount::where('status', 'active')->chunk(100, function ($accounts) use ($virtualAccountService, $bar, &$successCount, &$failCount) {
            foreach ($accounts as $va) {
                try {
                    $accountNumber = $va->palmpay_account_number ?? $va->account_number;
                    
                    if ($accountNumber) {
                        // Call PalmPay to delete the account
                        $response = $virtualAccountService->deleteVirtualAccount($accountNumber);
                        
                        if (!$response['success']) {
                            Log::warning("Failed to delete virtual account on PalmPay: {$accountNumber}", ['response' => $response]);
                        }
                    }

                    // Always update local database status to inactive to prevent deposits
                    $va->update([
                        'status' => 'inactive',
                        'palmpay_status' => 'inactive'
                    ]);

                    $successCount++;
                } catch (\Exception $e) {
                    $failCount++;
                    Log::error("Error disabling virtual account ID {$va->id}: " . $e->getMessage());
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Operation completed.");
        $this->info("Successfully disabled: {$successCount}");
        if ($failCount > 0) {
            $this->error("Failed to disable: {$failCount}. Check logs for details.");
        }

        return 0;
    }
}
