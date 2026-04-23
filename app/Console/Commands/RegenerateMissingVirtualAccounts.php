<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\VirtualAccount;
use App\Models\GlobalKycPool;
use App\Services\GlobalKycService;
use App\Services\PalmPay\VirtualAccountService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RegenerateMissingVirtualAccounts extends Command
{
    protected $signature = 'kyc:regenerate-missing-accounts 
                            {--company-id= : Specific company ID to process}
                            {--company-name= : Company name to search for}
                            {--dry-run : Show what would be done without making changes}
                            {--assign-fresh-kyc : Assign fresh KYC from pool to company before regenerating}';

    protected $description = 'Regenerate missing virtual accounts for company users and optionally assign fresh KYC';

    private $globalKycService;
    private $virtualAccountService;

    public function __construct()
    {
        parent::__construct();
        $this->globalKycService = new GlobalKycService();
        $this->virtualAccountService = new VirtualAccountService();
    }

    public function handle()
    {
        $this->info('=== REGENERATE MISSING VIRTUAL ACCOUNTS ===');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $assignFreshKyc = $this->option('assign-fresh-kyc');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Step 1: Find the company
        $company = $this->findCompany();
        if (!$company) {
            $this->error('Company not found!');
            return 1;
        }

        $this->displayCompanyInfo($company);

        // Step 2: Check Global KYC Pool Status
        $this->displayGlobalKycPoolStatus();

        // Step 3: Check company's current KYC
        $this->displayCompanyKycStatus($company);

        // Step 4: Find users without virtual accounts
        $usersWithoutVA = $this->findUsersWithoutVirtualAccounts($company);

        if ($usersWithoutVA->isEmpty()) {
            $this->info('✅ All users have virtual accounts!');
            return 0;
        }

        $this->warn("⚠️  Found {$usersWithoutVA->count()} users without virtual accounts");
        $this->newLine();

        // Step 5: Assign fresh KYC if requested
        if ($assignFreshKyc) {
            if (!$dryRun) {
                $this->assignFreshKycToCompany($company);
            } else {
                $this->info('Would assign fresh KYC from global pool to company');
            }
            $this->newLine();
        }

        // Step 6: Regenerate virtual accounts
        $this->regenerateVirtualAccounts($company, $usersWithoutVA, $dryRun);

        return 0;
    }

    private function findCompany(): ?Company
    {
        if ($companyId = $this->option('company-id')) {
            return Company::find($companyId);
        }

        if ($companyName = $this->option('company-name')) {
            return Company::where('name', 'LIKE', "%{$companyName}%")->first();
        }

        // Interactive selection
        $companyName = $this->ask('Enter company name to search');
        $companies = Company::where('name', 'LIKE', "%{$companyName}%")->get();

        if ($companies->isEmpty()) {
            return null;
        }

        if ($companies->count() === 1) {
            return $companies->first();
        }

        $choices = $companies->mapWithKeys(function ($company) {
            return [$company->id => "{$company->name} (ID: {$company->id}, Email: {$company->email})"];
        })->toArray();

        $selectedId = $this->choice('Multiple companies found. Select one:', $choices);
        return Company::find($selectedId);
    }

    private function displayCompanyInfo(Company $company): void
    {
        $this->info('📋 COMPANY INFORMATION');
        $this->table(
            ['Field', 'Value'],
            [
                ['Company ID', $company->id],
                ['Name', $company->name],
                ['Email', $company->email],
                ['Director BVN', $company->director_bvn ? substr($company->director_bvn, 0, 5) . '***' : 'NULL'],
                ['Director NIN', $company->director_nin ? substr($company->director_nin, 0, 5) . '***' : 'NULL'],
                ['Backup Director 1 BVN', $company->backup_director_1_bvn ? substr($company->backup_director_1_bvn, 0, 5) . '***' : 'NULL'],
                ['Backup Director 1 NIN', $company->backup_director_1_nin ? substr($company->backup_director_1_nin, 0, 5) . '***' : 'NULL'],
            ]
        );
        $this->newLine();
    }

    private function displayGlobalKycPoolStatus(): void
    {
        $this->info('🏦 GLOBAL KYC POOL STATUS');
        
        $available = GlobalKycPool::available()->get();
        $blacklisted = GlobalKycPool::where('blacklisted_until', '>', now())->count();
        
        $this->line("Available KYC: {$available->count()}");
        $this->line("Blacklisted KYC: {$blacklisted}");
        $this->newLine();

        if ($available->isNotEmpty()) {
            $this->table(
                ['ID', 'Type', 'Number', 'Usage', 'Max Usage', 'Success Rate'],
                $available->map(function ($kyc) {
                    return [
                        $kyc->id,
                        strtoupper($kyc->kyc_type),
                        substr($kyc->kyc_number, 0, 5) . '***',
                        $kyc->usage_count,
                        $kyc->max_usage ?? 'Unlimited',
                        round($kyc->success_rate, 2) . '%'
                    ];
                })
            );
        }
        $this->newLine();
    }

    private function displayCompanyKycStatus(Company $company): void
    {
        $this->info('🔑 COMPANY KYC STATUS');
        
        $hasDirectorBvn = !empty($company->director_bvn);
        $hasDirectorNin = !empty($company->director_nin);
        $hasBackupBvn = !empty($company->backup_director_1_bvn);
        $hasBackupNin = !empty($company->backup_director_1_nin);

        $status = [];
        $status[] = ['Director BVN', $hasDirectorBvn ? '✅ Available' : '❌ Missing'];
        $status[] = ['Director NIN', $hasDirectorNin ? '✅ Available' : '❌ Missing'];
        $status[] = ['Backup Director 1 BVN', $hasBackupBvn ? '✅ Available' : '❌ Missing'];
        $status[] = ['Backup Director 1 NIN', $hasBackupNin ? '✅ Available' : '❌ Missing'];

        $this->table(['KYC Type', 'Status'], $status);
        $this->newLine();
    }

    private function findUsersWithoutVirtualAccounts(Company $company)
    {
        return CompanyUser::where('company_id', $company->id)
            ->whereDoesntHave('virtualAccounts')
            ->get();
    }

    private function assignFreshKycToCompany(Company $company): void
    {
        $this->info('🔄 Assigning fresh KYC from global pool...');

        // Get optimal KYC from pool
        $globalKyc = $this->globalKycService->selectOptimalGlobalKyc();

        if (!$globalKyc) {
            $this->error('❌ No available KYC in global pool!');
            return;
        }

        // Assign to backup director slot
        if (empty($company->backup_director_1_bvn) && empty($company->backup_director_1_nin)) {
            if ($globalKyc->kyc_type === 'bvn') {
                $company->backup_director_1_bvn = $globalKyc->kyc_number;
            } else {
                $company->backup_director_1_nin = $globalKyc->kyc_number;
            }
            $company->save();

            $this->info("✅ Assigned {$globalKyc->kyc_type} ({$globalKyc->kyc_number}) to backup_director_1");
        } else {
            $this->warn('⚠️  Backup director slots already filled, skipping assignment');
        }
    }

    private function regenerateVirtualAccounts(Company $company, $users, bool $dryRun): void
    {
        $this->info('🔧 REGENERATING VIRTUAL ACCOUNTS');
        $this->newLine();

        $this->table(
            ['User ID', 'Email', 'Phone'],
            $users->map(fn($u) => [$u->id, $u->email, $u->phone])
        );
        $this->newLine();

        if ($dryRun) {
            $this->warn("Would regenerate {$users->count()} virtual accounts");
            return;
        }

        if (!$this->confirm('Proceed with regenerating virtual accounts?', true)) {
            $this->warn('Operation cancelled');
            return;
        }

        $successCount = 0;
        $failCount = 0;

        $progressBar = $this->output->createProgressBar($users->count());
        $progressBar->start();

        foreach ($users as $user) {
            try {
                $customerData = [
                    'email' => $user->email,
                    'phone_number' => $user->phone,
                    'first_name' => $user->first_name ?? 'User',
                    'last_name' => $user->last_name ?? $user->id,
                ];

                $virtualAccount = $this->virtualAccountService->createVirtualAccount(
                    $company->id,
                    $user->id,
                    $customerData,
                    '100033', // PalmPay bank code
                    $user->id
                );

                $successCount++;
                Log::info('RegenerateVA: Success', [
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'account_number' => $virtualAccount->account_number
                ]);

            } catch (\Exception $e) {
                $failCount++;
                Log::error('RegenerateVA: Failed', [
                    'company_id' => $company->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }

            $progressBar->advance();
            sleep(1); // Rate limiting
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Success: {$successCount}");
        $this->error("❌ Failed: {$failCount}");
        $this->newLine();

        if ($failCount > 0) {
            $this->warn('Check logs for detailed error information');
        }
    }
}
