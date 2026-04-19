<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\GlobalKycPool;
use App\Services\GlobalKycService;
use App\Services\PalmPay\VirtualAccountService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AssignFreshKycToCompany extends Command
{
    protected $signature = 'company:assign-fresh-kyc 
                            {company_id : The company ID to assign fresh KYC}
                            {--type=nin : KYC type to assign (bvn or nin)}
                            {--regenerate : Regenerate failed virtual accounts}
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Assign a fresh BVN/NIN from global pool to a company and optionally regenerate failed virtual accounts';

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
        $companyId = $this->argument('company_id');
        $kycType = $this->option('type');
        $regenerate = $this->option('regenerate');
        $dryRun = $this->option('dry-run');

        // Validate KYC type
        if (!in_array($kycType, ['bvn', 'nin'])) {
            $this->error("Invalid KYC type. Must be 'bvn' or 'nin'");
            return 1;
        }

        // Find company
        $company = Company::find($companyId);
        if (!$company) {
            $this->error("Company not found with ID: {$companyId}");
            return 1;
        }

        $this->info("=== Assign Fresh KYC to Company ===");
        $this->info("Company: {$company->name} (ID: {$company->id})");
        $this->info("KYC Type: " . strtoupper($kycType));
        
        if ($dryRun) {
            $this->warn("DRY RUN MODE - No changes will be made");
        }

        // Show current KYC status
        $this->info("\n--- Current KYC Status ---");
        $this->info("Director BVN: " . ($company->director_bvn ? substr($company->director_bvn, 0, 5) . '***' : 'Not set'));
        $this->info("Director NIN: " . ($company->director_nin ? substr($company->director_nin, 0, 5) . '***' : 'Not set'));

        // Check backup directors
        $backupCount = 0;
        for ($i = 2; $i <= 10; $i++) {
            $bvnField = "backup_director_{$i}_bvn";
            $ninField = "backup_director_{$i}_nin";
            if ($company->$bvnField || $company->$ninField) {
                $backupCount++;
            }
        }
        $this->info("Backup Directors: {$backupCount}");

        // Check available global KYC
        $availableKyc = GlobalKycPool::available()->byType($kycType)->count();
        $this->info("\n--- Global KYC Pool ---");
        $this->info("Available {$kycType}: {$availableKyc}");

        if ($availableKyc === 0) {
            $this->error("No available {$kycType} in global pool!");
            return 1;
        }

        // Select optimal KYC from pool
        $selectedKyc = $this->globalKycService->selectOptimalGlobalKyc($kycType);
        if (!$selectedKyc) {
            $this->error("Failed to select KYC from global pool");
            return 1;
        }

        $this->info("\n--- Selected KYC ---");
        $this->info("KYC ID: {$selectedKyc->id}");
        $this->info("KYC Number: " . substr($selectedKyc->kyc_number, 0, 5) . '***');
        $this->info("Usage Count: {$selectedKyc->usage_count}");
        $this->info("Success Rate: " . round($selectedKyc->success_rate, 2) . "%");

        // Find next available backup director slot
        $nextSlot = null;
        for ($i = 2; $i <= 10; $i++) {
            $bvnField = "backup_director_{$i}_bvn";
            $ninField = "backup_director_{$i}_nin";
            
            if ($kycType === 'bvn' && empty($company->$bvnField)) {
                $nextSlot = $i;
                break;
            } elseif ($kycType === 'nin' && empty($company->$ninField)) {
                $nextSlot = $i;
                break;
            }
        }

        if (!$nextSlot) {
            $this->error("No available backup director slots for {$kycType}!");
            $this->info("All backup director slots (2-10) are filled.");
            return 1;
        }

        $fieldName = $kycType === 'bvn' ? "backup_director_{$nextSlot}_bvn" : "backup_director_{$nextSlot}_nin";
        
        $this->info("\n--- Assignment Plan ---");
        $this->info("Will assign to: {$fieldName}");
        $this->info("This will be backup director #{$nextSlot}");

        // Assign KYC to company
        if (!$dryRun) {
            if (!$this->confirm('Proceed with KYC assignment?', true)) {
                $this->info('Operation cancelled');
                return 0;
            }

            DB::beginTransaction();
            try {
                $company->update([
                    $fieldName => $selectedKyc->kyc_number
                ]);

                $this->info("\n✓ KYC assigned successfully!");
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Failed to assign KYC: " . $e->getMessage());
                Log::error('AssignFreshKyc: Failed to assign KYC', [
                    'company_id' => $companyId,
                    'error' => $e->getMessage()
                ]);
                return 1;
            }
        } else {
            $this->info("\n[DRY RUN] Would assign KYC to {$fieldName}");
        }

        // Regenerate failed virtual accounts if requested
        if ($regenerate) {
            $this->info("\n=== Regenerating Failed Virtual Accounts ===");
            
            // Find company users without virtual accounts
            $companyUsers = DB::table('company_users')
                ->where('company_id', $companyId)
                ->whereNotExists(function ($query) use ($companyId) {
                    $query->select(DB::raw(1))
                        ->from('virtual_accounts')
                        ->whereColumn('virtual_accounts.company_user_id', 'company_users.id')
                        ->where('virtual_accounts.company_id', $companyId)
                        ->where('virtual_accounts.status', 'active')
                        ->whereNull('virtual_accounts.deleted_at');
                })
                ->get();

            $this->info("Found {$companyUsers->count()} users without virtual accounts");

            if ($companyUsers->count() > 0 && !$dryRun) {
                if (!$this->confirm('Proceed with regenerating virtual accounts?', true)) {
                    $this->info('Regeneration cancelled');
                    return 0;
                }

                $successCount = 0;
                $failCount = 0;

                $progressBar = $this->output->createProgressBar($companyUsers->count());
                $progressBar->start();

                foreach ($companyUsers as $companyUser) {
                    try {
                        // Get user details
                        $user = DB::table('users')->find($companyUser->user_id);
                        if (!$user) {
                            $failCount++;
                            $progressBar->advance();
                            continue;
                        }

                        // Create virtual account
                        $customerData = [
                            'name' => $user->name ?? 'Customer',
                            'email' => $user->email,
                            'phone' => $user->phone,
                            'account_type' => 'static'
                        ];

                        $virtualAccount = $this->virtualAccountService->createVirtualAccount(
                            $companyId,
                            $user->id,
                            $customerData,
                            '100033',
                            $companyUser->id
                        );

                        $successCount++;
                        
                    } catch (\Exception $e) {
                        $failCount++;
                        Log::error('AssignFreshKyc: Failed to regenerate virtual account', [
                            'company_id' => $companyId,
                            'user_id' => $companyUser->user_id,
                            'error' => $e->getMessage()
                        ]);
                    }

                    $progressBar->advance();
                }

                $progressBar->finish();
                $this->newLine(2);

                $this->info("✓ Regeneration complete!");
                $this->info("Success: {$successCount}");
                $this->info("Failed: {$failCount}");
            } elseif ($dryRun) {
                $this->info("[DRY RUN] Would regenerate {$companyUsers->count()} virtual accounts");
            }
        }

        $this->newLine();
        $this->info("=== Operation Complete ===");
        
        return 0;
    }
}
