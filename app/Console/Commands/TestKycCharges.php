<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Company;

class TestKycCharges extends Command
{
    protected $signature = 'kyc:test-charges';
    protected $description = 'Test KYC charges configuration and verify external charges are working';

    public function handle()
    {
        $this->info('========================================');
        $this->info('  KYC CHARGES CONFIGURATION TEST');
        $this->info('========================================');
        $this->newLine();

        // Test 1: Check service_charges table
        $this->info('TEST 1: Checking service_charges table...');
        $this->line('-------------------------------------------');
        
        try {
            $charges = DB::table('service_charges')
                ->where('service_category', 'kyc')
                ->orderBy('service_name')
                ->get();

            if ($charges->isEmpty()) {
                $this->error('❌ NO KYC charges configured!');
                $this->newLine();
                $this->warn('To configure charges, run:');
                $this->line('php artisan kyc:setup-charges');
                return 1;
            }

            $this->info('✅ KYC charges found: ' . $charges->count() . ' services');
            $this->newLine();

            foreach ($charges as $charge) {
                $status = $charge->is_active ? '✅ Active' : '❌ Inactive';
                $scope = $charge->company_id == 1 ? 'Global' : "Company #{$charge->company_id}";
                $this->line("  • {$charge->service_name}: ₦{$charge->charge_value} ($status, $scope)");
            }

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }

        // Test 2: Check companies and KYC status
        $this->newLine();
        $this->info('TEST 2: Checking company KYC status...');
        $this->line('-------------------------------------------');

        $companies = Company::orderBy('id')->limit(10)->get();

        if ($companies->isEmpty()) {
            $this->warn('⚠️  No companies found');
        } else {
            $this->info('Found ' . $companies->count() . ' companies:');
            $this->newLine();

            foreach ($companies as $company) {
                $status = $company->kyc_status;
                $willCharge = in_array($status, ['verified', 'approved']) ? '💰 WILL CHARGE' : '🆓 FREE (onboarding)';
                $this->line("  • Company #{$company->id}: {$company->name}");
                $this->line("    Status: $status → $willCharge");
            }
        }

        // Test 3: Check wallets
        $this->newLine();
        $this->info('TEST 3: Checking company wallet balances...');
        $this->line('-------------------------------------------');

        $wallets = DB::table('company_wallets')
            ->join('companies', 'companies.id', '=', 'company_wallets.company_id')
            ->select('company_wallets.company_id', 'companies.name', 'company_wallets.balance')
            ->orderBy('company_wallets.company_id')
            ->limit(10)
            ->get();

        if ($wallets->isEmpty()) {
            $this->warn('⚠️  No company wallets found');
        } else {
            $this->info('Found ' . $wallets->count() . ' wallets:');
            $this->newLine();

            foreach ($wallets as $wallet) {
                $balance = number_format($wallet->balance, 2);
                $canAffordBVN = $wallet->balance >= 100 ? '✅' : '❌';
                $this->line("  • Company #{$wallet->company_id}: {$wallet->name}");
                $this->line("    Balance: ₦$balance $canAffordBVN");
            }
        }

        // Test 4: Check recent KYC transactions
        $this->newLine();
        $this->info('TEST 4: Checking recent KYC transactions...');
        $this->line('-------------------------------------------');

        $transactions = DB::table('transactions')
            ->join('companies', 'companies.id', '=', 'transactions.company_id')
            ->where('transactions.category', 'kyc_charge')
            ->select('transactions.id', 'transactions.company_id', 'companies.name', 
                     'transactions.amount', 'transactions.status', 'transactions.description', 
                     'transactions.created_at')
            ->orderBy('transactions.created_at', 'desc')
            ->limit(5)
            ->get();

        if ($transactions->isEmpty()) {
            $this->info('ℹ️  No KYC transactions yet (charges not tested)');
        } else {
            $this->info('Found ' . $transactions->count() . ' recent KYC transactions:');
            $this->newLine();

            foreach ($transactions as $tx) {
                $status = $tx->status === 'success' ? '✅' : '❌';
                $this->line("  • TX #{$tx->id}: {$tx->name}");
                $this->line("    Amount: ₦{$tx->amount} | Status: {$tx->status} $status");
                $this->line("    Description: {$tx->description}");
                $this->line("    Date: {$tx->created_at}");
                $this->newLine();
            }
        }

        // Test 5: Check EaseID configuration
        $this->newLine();
        $this->info('TEST 5: Checking EaseID API configuration...');
        $this->line('-------------------------------------------');

        $easeIdConfigured = true;
        $requiredVars = ['easeid.app_id', 'easeid.private_key', 'easeid.base_url'];

        foreach ($requiredVars as $var) {
            $value = config($var);
            if (empty($value)) {
                $this->error("❌ $var not configured");
                $easeIdConfigured = false;
            } else {
                $this->info("✅ $var configured");
            }
        }

        if (!$easeIdConfigured) {
            $this->newLine();
            $this->warn('Add these to your .env file:');
            $this->line('EASEID_APP_ID=your_app_id');
            $this->line('EASEID_PRIVATE_KEY=your_private_key');
            $this->line('EASEID_BASE_URL=https://open-api.easeid.ai');
        }

        // Summary
        $this->newLine();
        $this->info('========================================');
        $this->info('  SUMMARY');
        $this->info('========================================');
        $this->newLine();

        $activeCharges = DB::table('service_charges')
            ->where('service_category', 'kyc')
            ->where('is_active', 1)
            ->count();

        if ($activeCharges >= 3) {
            $this->info("✅ KYC charges configured ($activeCharges active services)");
        } else {
            $this->error('❌ KYC charges NOT properly configured');
        }

        $companyCount = Company::count();
        if ($companyCount > 0) {
            $this->info("✅ Companies exist ($companyCount found)");
        } else {
            $this->warn('⚠️  No companies found');
        }

        $walletsWithBalance = DB::table('company_wallets')->where('balance', '>', 0)->count();
        if ($walletsWithBalance > 0) {
            $this->info("✅ Wallets with balance ($walletsWithBalance found)");
        } else {
            $this->warn('⚠️  No wallets with balance (fund wallet for testing)');
        }

        if ($easeIdConfigured) {
            $this->info('✅ EaseID API configured');
        } else {
            $this->error('❌ EaseID API NOT configured');
        }

        // Quick commands
        $this->newLine();
        $this->info('========================================');
        $this->info('  QUICK TEST COMMANDS');
        $this->info('========================================');
        $this->newLine();

        $this->line('1. Setup KYC charges:');
        $this->comment('   php artisan kyc:setup-charges');
        $this->newLine();

        $this->line('2. Add test balance to wallet:');
        $this->comment('   php artisan tinker');
        $this->comment('   >>> DB::table("company_wallets")->where("company_id", 1)->update(["balance" => 1000]);');
        $this->newLine();

        $this->line('3. Clear caches:');
        $this->comment('   php artisan config:clear && php artisan cache:clear && php artisan route:clear');
        $this->newLine();

        $this->line('4. Check routes:');
        $this->comment('   php artisan route:list | grep kyc');
        $this->newLine();

        return 0;
    }
}
