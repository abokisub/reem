<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\VirtualAccount;
use App\Services\PalmPay\VirtualAccountService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RegenerateVirtualAccountsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'virtualaccounts:regenerate
                            {--company-id=17 : The company ID to process}
                            {--customer-id= : Specific customer ID to process}
                            {--dry-run : Show what would be done without making changes}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate missing virtual accounts for company customers';

    private VirtualAccountService $vaService;

    public function __construct(VirtualAccountService $vaService)
    {
        parent::__construct();
        $this->vaService = $vaService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $companyId = $this->option('company-id');
        $customerId = $this->option('customer-id');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('========================================');
        $this->info('Virtual Account Regeneration');
        $this->info('========================================');
        $this->line("Company ID: {$companyId}");
        $this->line("Mode: " . ($dryRun ? "DRY RUN" : "LIVE"));
        if ($customerId) {
            $this->line("Target: Customer ID {$customerId}");
        }
        $this->info('========================================');
        $this->newLine();

        // Step 1: Verify company
        $this->task('Verifying company', function () use ($companyId, &$company) {
            $company = Company::find($companyId);
            return $company !== null;
        });

        if (!$company) {
            $this->error("Company with ID {$companyId} not found!");
            return 1;
        }

        $this->line("Company: {$company->name}");
        $this->line("Status: {$company->status}");
        $this->line("Active: " . ($company->is_active ? 'Yes' : 'No'));
        $this->newLine();

        // Step 2: Find customers without virtual accounts
        $this->info('Finding customers without virtual accounts...');

        $query = CompanyUser::where('company_id', $companyId)
            ->where('status', 'active')
            ->whereDoesntHave('virtualAccounts', function($q) {
                $q->where('status', 'active')
                  ->whereNull('deleted_at');
            });

        if ($customerId) {
            $query->where('id', $customerId);
        }

        $customersWithoutVA = $query->get();

        $this->info("Found {$customersWithoutVA->count()} customers without virtual accounts");
        $this->newLine();

        if ($customersWithoutVA->isEmpty()) {
            $this->info('✅ All customers already have virtual accounts!');
            return 0;
        }

        // Step 3: Display customers
        $this->table(
            ['ID', 'Name', 'Email', 'Phone'],
            $customersWithoutVA->map(function ($customer) {
                return [
                    $customer->id,
                    trim($customer->first_name . ' ' . $customer->last_name),
                    $customer->email ?? 'N/A',
                    $customer->phone ?? 'N/A',
                ];
            })
        );

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE: No accounts will be created');
            $this->line('Run without --dry-run to actually create accounts');
            return 0;
        }

        // Step 4: Confirm
        if (!$force) {
            if (!$this->confirm("Create {$customersWithoutVA->count()} virtual accounts?", true)) {
                $this->warn('Aborted by user');
                return 0;
            }
        }

        // Step 5: Create virtual accounts
        $this->newLine();
        $this->info('Creating virtual accounts...');
        $this->newLine();

        $successCount = 0;
        $failureCount = 0;
        $errors = [];

        $progressBar = $this->output->createProgressBar($customersWithoutVA->count());
        $progressBar->start();

        foreach ($customersWithoutVA as $customer) {
            $fullName = trim($customer->first_name . ' ' . $customer->last_name);

            try {
                // Prepare customer data
                $customerData = [
                    'name' => $fullName,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'account_type' => 'static',
                ];

                // Add customer KYC if available
                if ($customer->nin) {
                    $customerData['nin'] = $customer->nin;
                }

                // Create virtual account
                $virtualAccount = $this->vaService->createVirtualAccount(
                    $companyId,
                    $customer->uuid,
                    $customerData,
                    '100033', // PalmPay bank code
                    $customer->id
                );

                Log::info('VirtualAccount: Regenerated successfully', [
                    'customer_id' => $customer->id,
                    'customer_name' => $fullName,
                    'account_number' => $virtualAccount->account_number,
                    'kyc_source' => $virtualAccount->kyc_source
                ]);

                $successCount++;

            } catch (\Exception $e) {
                Log::error('VirtualAccount: Regeneration failed', [
                    'customer_id' => $customer->id,
                    'customer_name' => $fullName,
                    'error' => $e->getMessage()
                ]);

                $failureCount++;
                $errors[] = [
                    'customer_id' => $customer->id,
                    'customer_name' => $fullName,
                    'error' => $e->getMessage()
                ];
            }

            $progressBar->advance();

            // Small delay to avoid rate limiting
            usleep(500000); // 0.5 seconds
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('========================================');
        $this->info('SUMMARY');
        $this->info('========================================');
        $this->line("Total Customers: {$customersWithoutVA->count()}");
        $this->line("✅ Successful: {$successCount}");
        $this->line("❌ Failed: {$failureCount}");
        $this->info('========================================');
        $this->newLine();

        if ($failureCount > 0) {
            $this->error('Failed Customers:');
            $this->table(
                ['Customer ID', 'Name', 'Error'],
                collect($errors)->map(fn($e) => [
                    $e['customer_id'],
                    $e['customer_name'],
                    substr($e['error'], 0, 60) . '...'
                ])
            );

            $this->newLine();
            $this->line('💡 Check logs for details: tail -f storage/logs/laravel.log | grep "VirtualAccount"');
        }

        if ($successCount > 0) {
            $this->info('✅ Virtual accounts created successfully!');
        }

        return $failureCount > 0 ? 1 : 0;
    }
}
