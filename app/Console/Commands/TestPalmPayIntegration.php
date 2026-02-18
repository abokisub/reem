<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PalmPay\VirtualAccountService;
use App\Services\PalmPay\TransferService;
use App\Services\PalmPay\AccountVerificationService;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class TestPalmPayIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'palmpay:test {type : The test type (va|transfer|banks)} 
                            {--email= : Email for VA test} 
                            {--name= : Name for VA test}
                            {--amount=100 : Amount for transfer test}
                            {--bank=044 : Bank code for transfer/lookup (044=Access)}
                            {--account=0690000031 : Account number for transfer/lookup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test PalmPay Integration features';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->argument('type');

        $this->info("Starting PalmPay Integration Test: " . strtoupper($type));

        try {
            switch ($type) {
                case 'va':
                    $this->testVirtualAccount();
                    break;
                case 'transfer':
                    $this->testTransfer();
                    break;
                case 'banks':
                    $this->testBanks();
                    break;
                default:
                    $this->error("Invalid test type. Use 'va', 'transfer', or 'banks'.");
                    return 1;
            }
        } catch (\Exception $e) {
            $this->error("Test Failed: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        $this->info("Test Completed Successfully.");
        return 0;
    }

    private function getTestCompany()
    {
        // Try to find an existing company or create a dummy one
        $company = Company::first();
        
        if (!$company) {
            $this->info("No existing company found. Creating a test company...");
            $company = Company::create([
                'name' => 'Test Company',
                'email' => 'test_company_' . time() . '@example.com',
                'phone' => '08012345678',
                'public_key' => 'pk_test_' . \Str::random(20),
                'secret_key' => 'sk_test_' . \Str::random(20),
                'api_key' => 'ak_test_' . \Str::random(20),
                'status' => 'active',
                'webhook_url' => 'https://example.com/webhook',
                'webhook_secret' => \Str::random(32),
            ]);
            $this->info("Created Test Company ID: " . $company->id);
        } else {
            $this->info("Using Company ID: " . $company->id);
        }

        return $company;
    }

    private function testVirtualAccount()
    {
        $service = app(VirtualAccountService::class);
        $company = $this->getTestCompany();

        $userId = 'test_user_' . time();
        $name = $this->option('name') ?: 'Test User ' . rand(100, 999);
        $email = $this->option('email') ?: 'test' . time() . '@example.com';

        $customerData = [
            'name' => $name,
            'email' => $email,
            'phone' => '080' . rand(10000000, 99999999),
            // 'bvn' => '12345678901', // Optional
        ];

        $this->info("Attempting to create Virtual Account for user: $userId");
        $this->info("Customer Data: " . json_encode($customerData));

        $va = $service->createVirtualAccount($company->id, $userId, $customerData);

        $this->info("Virtual Account Created!");
        $this->info("Account Number: " . $va->palmpay_account_number);
        $this->info("Account Name: " . $va->palmpay_account_name);
        $this->info("Bank Name: " . $va->palmpay_bank_name);
        $this->info("Status: " . $va->status);
    }

    private function testTransfer()
    {
        $transferService = app(TransferService::class);
        $verificationService = app(AccountVerificationService::class);
        $company = $this->getTestCompany();
        
        $bankCode = $this->option('bank');
        $accountNumber = $this->option('account');

        $this->info("1. Validating Account Details...");
        $this->info("Bank Code: $bankCode, Account: $accountNumber");

        $accountName = "Unknown Beneficiary";

        try {
            $result = $verificationService->verifyAccount($accountNumber, $bankCode);
            if (!$result['success']) {
                $this->error("Verification failed: " . ($result['message'] ?? 'Unknown error'));
            } else {
                $accountName = $result['account_name'];
                $this->info("Account Validated! Name: " . $accountName);
            }
        } catch (\Exception $e) {
            $this->error("Account Validation Failed: " . $e->getMessage());
        }

        if ($accountName === "Unknown Beneficiary") {
             if (!$this->confirm("Validation failed. Do you want to force try the transfer anyway?", false)) {
                return;
            }
        }

        if ($this->confirm("Do you want to proceed with a REAL transfer of NGN " . $this->option('amount') . "?", true)) {
            $transferData = [
                'amount' => $this->option('amount'),
                'bank_code' => $bankCode,
                'bank_name' => 'Unknown Bank', // We should ideally fetch bank name
                'account_number' => $accountNumber,
                'account_name' => $accountName,
                'reference' => 'txn_test_' . time(),
                'narration' => 'Integration Test Transfer',
                'currency' => 'NGN'
            ];

            $this->info("Initiating Transfer...");
            
            try {
                $txn = $transferService->initiateTransfer($company->id, $transferData);
                
                $this->info("Transfer Initiated!");
                $this->info("Transaction ID: " . $txn->transaction_id);
                $this->info("Reference: " . $txn->reference);
                $this->info("Status: " . $txn->status);
                $this->info("PalmPay Ref: " . $txn->palmpay_reference);
            } catch (\Exception $e) {
                 $this->error("Transfer Initiation Failed: " . $e->getMessage());
            }

        } else {
            $this->info("Transfer Cancelled.");
        }
    }

    private function testBanks()
    {
        $verificationService = app(AccountVerificationService::class);
        $this->info("Fetching Banks list from PalmPay...");
        
        $banks = $verificationService->getBanks();
        
        $this->info("Banks fetched successfully (Count: " . count($banks) . ")");
        if (count($banks) > 0) {
            $this->info("First 5 banks:");
            foreach (array_slice($banks, 0, 5) as $bank) {
                // Handle different bank object structures if needed
                $name = $bank['name'] ?? $bank['bankName'] ?? 'Unknown';
                $code = $bank['code'] ?? $bank['bankCode'] ?? 'N/A';
                $this->info("- " . $name . " (" . $code . ")");
            }
        }
    }
}