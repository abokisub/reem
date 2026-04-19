<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\GlobalKycPool;
use App\Services\GlobalKycService;
use App\Services\PalmPay\VirtualAccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmergencyKycFix extends Command
{
    protected $signature = 'emergency:kyc-fix 
                            {--check-only : Only check status without fixing}
                            {--company= : Specific company ID to fix}
                            {--auto-fix : Automatically fix all issues without prompts}';

    protected $description = 'Emergency fix for companies that hit KYC limits - assigns fresh KYC and regenerates accounts';

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
        $checkOnly = $this->option('check-only');
        $specificCompany = $this->option('company');
        $autoFix = $this->option('auto-fix');

        $this->info("╔════════════════════════════════════════════════════════════╗");
        $this->info("║         EMERGENCY KYC FIX - SYSTEM HEALTH CHECK           ║");
        $this->info("╚════════════════════════════════════════════════════════════╝");
        $this->newLine();

        // Step 1: Check Global KYC Pool
        $this->checkGlobalKycPool();

        // Step 2: Check Companies with Issues
        $companiesWithIssues = $this->checkCompaniesWithIssues($specificCompany);

        if (empty($companiesWithIssues)) {
            $this->info("✓ No companies with KYC issues found!");
            return 0;
        }

        // Step 3: Show summary
        $this->showSummary($companiesWithIssues);

        if ($checkOnly) {
            $this->warn("\n--check-only mode: No fixes will be applied");
            return 0;
        }

        // Step 4: Fix issues
        if ($autoFix || $this->confirm('Do you want to fix all issues now?', true)) {
            $this->fixAllIssues($companiesWithIssues);
        } else {
            $this->info("\nNo changes made. Run without --check-only to apply fixes.");
        }

        return 0;
    }

    private function checkGlobalKycPool()
    {
        $this->info("📊 GLOBAL KYC POOL STATUS");
        $this->info("─────────────────────────────────────────────────────────────");

        $stats = $this->globalKycService->getUsageStats();
        $availableByType = $this->globalKycService->getAvailableKycByType();

        $this->line("Available BVN: {$availableByType['bvn']} / {$stats['pool_stats']['total_kyc']}");
        $this->line("Available NIN: {$availableByType['nin']} / {$stats['pool_stats']['total_kyc']}");
        $this->line("Blacklisted: {$stats['pool_stats']['blacklisted_kyc']}");
        $this->line("Overall Success Rate: {$stats['usage_stats']['overall_success_rate']}%");
        
        if ($availableByType['bvn'] === 0 && $availableByType['nin'] === 0) {
            $this->error("⚠️  WARNING: No available KYC in global pool!");
            $this->warn("Please add BVN/NIN to global pool before proceeding.");
        }

        $this->newLine();
    }

    private function checkCompaniesWithIssues($specificCompanyId = null)
    {
        $this->info("🔍 CHECKING COMPANIES FOR ISSUES");
        $this->info("─────────────────────────────────────────────────────────────");

        $query = Company::query();
        
        if ($specificCompanyId) {
            $query->where('id', $specificCompanyId);
        }

        $companies = $query->get();
        $companiesWithIssues = [];

        foreach ($companies as $company) {
            $issues = $this->analyzeCompany($company);
            
            if (!empty($issues['missing_accounts']) || $issues['needs_fresh_kyc']) {
                $companiesWithIssues[] = [
                    'company' => $company,
                    'issues' => $issues
                ];
                
                $this->warn("\n⚠️  {$company->name} (ID: {$company->id})");
                
                if ($issues['needs_fresh_kyc']) {
                    $this->line("   • KYC may have hit limit (has {$issues['backup_directors']} backup directors)");
                }
                
                if (!empty($issues['missing_accounts'])) {
                    $this->line("   • {$issues['missing_accounts']} users without virtual accounts");
                }
            }
        }

        $this->newLine();
        return $companiesWithIssues;
    }

    private function analyzeCompany($company)
    {
        // Count backup directors
        $backupCount = 0;
        for ($i = 2; $i <= 10; $i++) {
            $bvnField = "backup_director_{$i}_bvn";
            $ninField = "backup_director_{$i}_nin";
            if ($company->$bvnField || $company->$ninField) {
                $backupCount++;
            }
        }

        // Check for users without virtual accounts
        $totalUsers = DB::table('company_users')
            ->where('company_id', $company->id)
            ->count();

        $usersWithAccounts = DB::table('company_users')
            ->where('company_id', $company->id)
            ->whereExists(function ($query) use ($company) {
                $query->select(DB::raw(1))
                    ->from('virtual_accounts')
                    ->whereColumn('virtual_accounts.company_user_id', 'company_users.id')
                    ->where('virtual_accounts.company_id', $company->id)
                    ->where('virtual_accounts.status', 'active')
                    ->whereNull('virtual_accounts.deleted_at');
            })
            ->count();

        $missingAccounts = $totalUsers - $usersWithAccounts;

        // Check if company might need fresh KYC
        $needsFreshKyc = $missingAccounts > 0 && $backupCount < 9; // Has space for more backup directors

        return [
            'total_users' => $totalUsers,
            'users_with_accounts' => $usersWithAccounts,
            'missing_accounts' => $missingAccounts,
            'backup_directors' => $backupCount,
            'needs_fresh_kyc' => $needsFreshKyc,
            'has_director_bvn' => !empty($company->director_bvn),
            'has_director_nin' => !empty($company->director_nin),
        ];
    }

    private function showSummary($companiesWithIssues)
    {
        $this->info("📋 SUMMARY");
        $this->info("─────────────────────────────────────────────────────────────");

        $totalCompanies = count($companiesWithIssues);
        $totalMissingAccounts = array_sum(array_column(array_column($companiesWithIssues, 'issues'), 'missing_accounts'));

        $this->line("Companies with issues: {$totalCompanies}");
        $this->line("Total missing accounts: {$totalMissingAccounts}");
        $this->newLine();
    }

    private function fixAllIssues($companiesWithIssues)
    {
        $this->info("🔧 APPLYING FIXES");
        $this->info("─────────────────────────────────────────────────────────────");
        $this->newLine();

        foreach ($companiesWithIssues as $item) {
            $company = $item['company'];
            $issues = $item['issues'];

            $this->info("Fixing: {$company->name} (ID: {$company->id})");

            // Step 1: Assign fresh KYC if needed
            if ($issues['needs_fresh_kyc']) {
                $this->line("  → Assigning fresh KYC from global pool...");
                
                $kycType = 'nin'; // Prefer NIN
                $selectedKyc = $this->globalKycService->selectOptimalGlobalKyc($kycType);
                
                if ($selectedKyc) {
                    // Find next available backup slot
                    $nextSlot = null;
                    for ($i = 2; $i <= 10; $i++) {
                        $field = "backup_director_{$i}_{$kycType}";
                        if (empty($company->$field)) {
                            $nextSlot = $i;
                            break;
                        }
                    }

                    if ($nextSlot) {
                        $fieldName = "backup_director_{$nextSlot}_{$kycType}";
                        $company->update([$fieldName => $selectedKyc->kyc_number]);
                        $this->info("  ✓ Assigned {$kycType} to {$fieldName}");
                    } else {
                        $this->warn("  ⚠️  No available backup slots");
                    }
                } else {
                    $this->error("  ✗ No available KYC in global pool");
                    continue;
                }
            }

            // Step 2: Regenerate missing accounts
            if ($issues['missing_accounts'] > 0) {
                $this->line("  → Regenerating {$issues['missing_accounts']} virtual accounts...");
                
                $result = $this->regenerateAccounts($company);
                
                $this->info("  ✓ Success: {$result['success']} | Failed: {$result['failed']}");
            }

            $this->newLine();
        }

        $this->info("✓ All fixes applied!");
    }

    private function regenerateAccounts($company)
    {
        $companyUsers = DB::table('company_users')
            ->where('company_id', $company->id)
            ->whereNotExists(function ($query) use ($company) {
                $query->select(DB::raw(1))
                    ->from('virtual_accounts')
                    ->whereColumn('virtual_accounts.company_user_id', 'company_users.id')
                    ->where('virtual_accounts.company_id', $company->id)
                    ->where('virtual_accounts.status', 'active')
                    ->whereNull('virtual_accounts.deleted_at');
            })
            ->get();

        $successCount = 0;
        $failCount = 0;

        foreach ($companyUsers as $companyUser) {
            try {
                $user = DB::table('users')->find($companyUser->user_id);
                if (!$user) {
                    $failCount++;
                    continue;
                }

                $customerData = [
                    'name' => $user->name ?? 'Customer',
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'account_type' => 'static'
                ];

                $this->virtualAccountService->createVirtualAccount(
                    $company->id,
                    $user->id,
                    $customerData,
                    '100033',
                    $companyUser->id
                );

                $successCount++;
                
            } catch (\Exception $e) {
                $failCount++;
                Log::error('EmergencyKycFix: Failed to regenerate account', [
                    'company_id' => $company->id,
                    'user_id' => $companyUser->user_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'success' => $successCount,
            'failed' => $failCount
        ];
    }
}
