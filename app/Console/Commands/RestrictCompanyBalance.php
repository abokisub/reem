<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CompanyWallet;
use App\Models\Company;

class RestrictCompanyBalance extends Command
{
    protected $signature = 'company:restrict-balance 
                            {company_id : The company ID}
                            {amount : Amount to restrict (in Naira)}
                            {--reason= : Reason for restriction (e.g. fraud investigation)}
                            {--remove : Remove restriction (set to 0)}
                            {--show : Show current restriction status only}';

    protected $description = 'Restrict a company wallet balance (fraud hold). The company can see their full balance but cannot withdraw the restricted amount.';

    public function handle()
    {
        $companyId = $this->argument('company_id');
        $company = Company::find($companyId);

        if (!$company) {
            $this->error("Company ID {$companyId} not found.");
            return 1;
        }

        $wallet = CompanyWallet::where('company_id', $companyId)
            ->where('currency', 'NGN')
            ->first();

        if (!$wallet) {
            $this->error("No NGN wallet found for {$company->name}.");
            return 1;
        }

        // Show current status
        $this->info("=== Company Wallet Status ===");
        $this->info("Company: {$company->name} (ID: {$companyId})");
        $this->table(
            ['Field', 'Value'],
            [
                ['Total Balance', '₦' . number_format($wallet->balance, 2)],
                ['Restricted Balance', '₦' . number_format($wallet->restricted_balance, 2)],
                ['Available Balance', '₦' . number_format($wallet->availableBalance(), 2)],
                ['Ledger Balance', '₦' . number_format($wallet->ledger_balance, 2)],
                ['Pending Balance', '₦' . number_format($wallet->pending_balance, 2)],
            ]
        );

        if ($this->option('show')) {
            return 0;
        }

        if ($this->option('remove')) {
            $oldRestricted = $wallet->restricted_balance;
            $wallet->update(['restricted_balance' => 0]);
            $wallet->refresh();

            $this->info("\n=== Restriction Removed ===");
            $this->info("Previous Restriction: ₦" . number_format($oldRestricted, 2));
            $this->info("New Available Balance: ₦" . number_format($wallet->availableBalance(), 2));

            \Illuminate\Support\Facades\Log::warning('FRAUD: Balance restriction REMOVED', [
                'company_id' => $companyId,
                'company_name' => $company->name,
                'previous_restricted' => $oldRestricted,
                'reason' => $this->option('reason') ?? 'Manual removal',
            ]);

            return 0;
        }

        $amount = (float) $this->argument('amount');

        if ($amount <= 0) {
            $this->error("Amount must be greater than 0.");
            return 1;
        }

        if ($amount > $wallet->balance) {
            $this->warn("WARNING: Restriction amount (₦" . number_format($amount, 2) . ") exceeds total balance (₦" . number_format($wallet->balance, 2) . ").");
            $this->warn("This will effectively freeze the entire wallet.");
            if (!$this->confirm('Continue?')) {
                return 1;
            }
        }

        $reason = $this->option('reason') ?? 'Fraud investigation';

        $this->warn("\n⚠️  You are about to restrict ₦" . number_format($amount, 2) . " from {$company->name}'s wallet.");
        $this->warn("Reason: {$reason}");
        $this->warn("They will only be able to withdraw: ₦" . number_format(max(0, $wallet->balance - $amount), 2));

        if (!$this->confirm('Proceed with restriction?')) {
            $this->info('Cancelled.');
            return 0;
        }

        $oldRestricted = $wallet->restricted_balance;
        $wallet->update(['restricted_balance' => $amount]);
        $wallet->refresh();

        $this->info("\n=== Restriction Applied ===");
        $this->table(
            ['Field', 'Value'],
            [
                ['Total Balance', '₦' . number_format($wallet->balance, 2)],
                ['NEW Restricted Balance', '₦' . number_format($wallet->restricted_balance, 2)],
                ['NEW Available Balance', '₦' . number_format($wallet->availableBalance(), 2)],
            ]
        );

        \Illuminate\Support\Facades\Log::warning('FRAUD: Balance restriction APPLIED', [
            'company_id' => $companyId,
            'company_name' => $company->name,
            'restricted_amount' => $amount,
            'previous_restricted' => $oldRestricted,
            'total_balance' => $wallet->balance,
            'available_balance' => $wallet->availableBalance(),
            'reason' => $reason,
        ]);

        $this->info("\n✅ Done. {$company->name} can see ₦" . number_format($wallet->balance, 2) . " but can only withdraw ₦" . number_format($wallet->availableBalance(), 2));

        return 0;
    }
}
