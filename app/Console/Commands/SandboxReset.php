<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyWallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SandboxReset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sandbox:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset sandbox environment: restore balances to 2M NGN and clear test data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!$this->isSandbox()) {
            $this->error('❌ This command can only run in sandbox mode!');
            return 1;
        }

        $this->info('🏗️ Starting Sandbox Reset...');

        DB::beginTransaction();

        try {
            // 1. Reset all company wallets to 2,000,000 NGN
            $companies = Company::all();
            $resetCount = 0;

            foreach ($companies as $company) {
                $wallet = CompanyWallet::firstOrCreate(
                    [
                        'company_id' => $company->id,
                        'currency' => 'NGN',
                    ],
                    [
                        'balance' => 2000000.00,
                        'available_balance' => 2000000.00,
                        'pending_balance' => 0.00,
                    ]
                );

                // Reset balance
                $wallet->update([
                    'balance' => 2000000.00,
                    'available_balance' => 2000000.00,
                    'pending_balance' => 0.00,
                ]);

                $resetCount++;
            }

            $this->info("✅ Reset {$resetCount} company wallets to 2,000,000 NGN");

            // 2. Clear test transactions (optional - keep for audit trail)
            // Uncomment if you want to clear transactions daily
            // DB::table('transactions')->where('is_test', true)->delete();
            // $this->info('✅ Cleared test transactions');

            // 3. Clear test webhook logs older than 7 days
            DB::table('company_webhook_logs')
                ->where('is_test', true)
                ->where('created_at', '<', now()->subDays(7))
                ->delete();
            $this->info('✅ Cleared old test webhook logs');

            // 4. Reset ledger accounts (optional)
            // This maintains double-entry integrity by creating reversal entries
            // Uncomment if needed
            // $this->resetLedgerAccounts();

            DB::commit();

            Log::info('Sandbox Reset Completed', [
                'companies_reset' => $resetCount,
                'timestamp' => now()->toDateTimeString(),
            ]);

            $this->info('🎉 Sandbox reset completed successfully!');
            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Sandbox reset failed: ' . $e->getMessage());
            Log::error('Sandbox Reset Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * Check if running in sandbox mode
     */
    private function isSandbox(): bool
    {
        return config('app.env') === 'sandbox' ||
            config('app.sandbox_mode', false) === true;
    }

    /**
     * Reset ledger accounts (optional)
     */
    private function resetLedgerAccounts(): void
    {
        // Create reversal entries for all test transactions
        // This maintains audit trail while resetting balances
        $ledgerService = app(\App\Services\LedgerService::class);

        // Implementation depends on your ledger structure
        // Example: Create reversal entries for all test ledger entries
        $testEntries = DB::table('ledger_entries')
            ->where('created_at', '>=', now()->subDay())
            ->get();

        foreach ($testEntries as $entry) {
            // Create reversal entry
            // $ledgerService->recordEntry(...)
        }
    }
}
