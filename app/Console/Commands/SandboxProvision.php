<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyWallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SandboxProvision extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sandbox:provision {company_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provision sandbox wallet with 2,000,000 NGN for testing';

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

        $companyId = $this->argument('company_id');

        if ($companyId) {
            return $this->provisionCompany($companyId);
        }

        // Provision all companies
        $companies = Company::all();
        $count = 0;

        foreach ($companies as $company) {
            if ($this->provisionCompany($company->id) === 0) {
                $count++;
            }
        }

        $this->info("✅ Provisioned {$count} companies with 2,000,000 NGN");
        return 0;
    }

    /**
     * Provision a specific company
     */
    private function provisionCompany(int $companyId): int
    {
        try {
            $company = Company::find($companyId);

            if (!$company) {
                $this->error("❌ Company {$companyId} not found");
                return 1;
            }

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

            // If wallet exists, update balance
            if ($wallet->wasRecentlyCreated) {
                $this->info("✅ Created wallet for {$company->name} with 2,000,000 NGN");
            } else {
                $wallet->update([
                    'balance' => 2000000.00,
                    'available_balance' => 2000000.00,
                    'pending_balance' => 0.00,
                ]);
                $this->info("✅ Reset wallet for {$company->name} to 2,000,000 NGN");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Failed to provision company {$companyId}: " . $e->getMessage());
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
}
