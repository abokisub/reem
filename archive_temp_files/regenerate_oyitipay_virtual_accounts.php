<?php

/**
 * Regenerate Missing Virtual Accounts for OyitiPay Customers
 * 
 * This script identifies customers without virtual accounts and creates them
 * using the fixed KYC retry logic.
 * 
 * Usage:
 *   php regenerate_oyitipay_virtual_accounts.php
 * 
 * Options:
 *   --company-id=17    Specify company ID (default: 17 for OyitiPay)
 *   --dry-run          Show what would be done without making changes
 *   --customer-id=629  Regenerate for specific customer only
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\VirtualAccount;
use App\Services\PalmPay\VirtualAccountService;
use Illuminate\Support\Facades\Log;

// Parse command line arguments
$options = getopt('', ['company-id:', 'dry-run', 'customer-id:', 'help']);

if (isset($options['help'])) {
    echo "Usage: php regenerate_oyitipay_virtual_accounts.php [options]\n\n";
    echo "Options:\n";
    echo "  --company-id=17    Specify company ID (default: 17 for OyitiPay)\n";
    echo "  --dry-run          Show what would be done without making changes\n";
    echo "  --customer-id=629  Regenerate for specific customer only\n";
    echo "  --help             Show this help message\n\n";
    exit(0);
}

$companyId = $options['company-id'] ?? 17; // OyitiPay company ID
$dryRun = isset($options['dry-run']);
$specificCustomerId = $options['customer-id'] ?? null;

echo "\n";
echo "========================================\n";
echo "Virtual Account Regeneration Script\n";
echo "========================================\n";
echo "Company ID: {$companyId}\n";
echo "Mode: " . ($dryRun ? "DRY RUN (no changes)" : "LIVE (will create accounts)") . "\n";
if ($specificCustomerId) {
    echo "Target: Customer ID {$specificCustomerId} only\n";
}
echo "========================================\n\n";

// Step 1: Verify company exists
echo "[1/5] Verifying company...\n";
$company = Company::find($companyId);

if (!$company) {
    echo "❌ ERROR: Company with ID {$companyId} not found!\n";
    exit(1);
}

echo "✅ Company found: {$company->name}\n";
echo "   - Status: {$company->status}\n";
echo "   - Active: " . ($company->is_active ? 'Yes' : 'No') . "\n\n";

// Step 2: Find customers without virtual accounts
echo "[2/5] Finding customers without virtual accounts...\n";

$query = CompanyUser::where('company_id', $companyId)
    ->where('status', 'active')
    ->whereDoesntHave('virtualAccounts', function($q) {
        $q->where('status', 'active')
          ->whereNull('deleted_at');
    });

if ($specificCustomerId) {
    $query->where('id', $specificCustomerId);
}

$customersWithoutVA = $query->get();

echo "Found {$customersWithoutVA->count()} customers without virtual accounts\n\n";

if ($customersWithoutVA->isEmpty()) {
    echo "✅ All customers already have virtual accounts!\n";
    exit(0);
}

// Step 3: Display customers
echo "[3/5] Customers to process:\n";
echo str_repeat("-", 80) . "\n";
printf("%-6s %-25s %-30s %-15s\n", "ID", "Name", "Email", "Phone");
echo str_repeat("-", 80) . "\n";

foreach ($customersWithoutVA as $customer) {
    $fullName = trim($customer->first_name . ' ' . $customer->last_name);
    printf(
        "%-6s %-25s %-30s %-15s\n",
        $customer->id,
        substr($fullName, 0, 25),
        substr($customer->email ?? 'N/A', 0, 30),
        $customer->phone ?? 'N/A'
    );
}
echo str_repeat("-", 80) . "\n\n";

if ($dryRun) {
    echo "🔍 DRY RUN MODE: No accounts will be created\n";
    echo "Run without --dry-run to actually create accounts\n";
    exit(0);
}

// Step 4: Confirm before proceeding
echo "[4/5] Ready to create virtual accounts\n";
echo "⚠️  This will create {$customersWithoutVA->count()} virtual accounts\n";
echo "Continue? (yes/no): ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "❌ Aborted by user\n";
    exit(0);
}

// Step 5: Create virtual accounts
echo "\n[5/5] Creating virtual accounts...\n\n";

$vaService = new VirtualAccountService();
$successCount = 0;
$failureCount = 0;
$errors = [];

foreach ($customersWithoutVA as $index => $customer) {
    $customerNum = $index + 1;
    $totalCustomers = $customersWithoutVA->count();
    $fullName = trim($customer->first_name . ' ' . $customer->last_name);
    
    echo "[{$customerNum}/{$totalCustomers}] Processing: {$fullName} (ID: {$customer->id})...\n";
    
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
            echo "   Using customer NIN: " . substr($customer->nin, 0, 5) . "***\n";
        }
        
        // Create virtual account
        $virtualAccount = $vaService->createVirtualAccount(
            $companyId,
            $customer->uuid,
            $customerData,
            '100033', // PalmPay bank code
            $customer->id
        );
        
        echo "   ✅ SUCCESS: Account created\n";
        echo "      Account Number: {$virtualAccount->account_number}\n";
        echo "      Account Name: {$virtualAccount->account_name}\n";
        echo "      Bank: {$virtualAccount->bank_name}\n";
        echo "      KYC Source: {$virtualAccount->kyc_source}\n";
        
        $successCount++;
        
    } catch (\Exception $e) {
        echo "   ❌ FAILED: {$e->getMessage()}\n";
        
        $failureCount++;
        $errors[] = [
            'customer_id' => $customer->id,
            'customer_name' => $fullName,
            'error' => $e->getMessage()
        ];
    }
    
    echo "\n";
    
    // Small delay to avoid rate limiting
    usleep(500000); // 0.5 seconds
}

// Summary
echo "\n";
echo "========================================\n";
echo "SUMMARY\n";
echo "========================================\n";
echo "Total Customers: {$customersWithoutVA->count()}\n";
echo "✅ Successful: {$successCount}\n";
echo "❌ Failed: {$failureCount}\n";
echo "========================================\n\n";

if ($failureCount > 0) {
    echo "Failed Customers:\n";
    echo str_repeat("-", 80) . "\n";
    
    foreach ($errors as $error) {
        echo "Customer ID: {$error['customer_id']}\n";
        echo "Name: {$error['customer_name']}\n";
        echo "Error: {$error['error']}\n";
        echo str_repeat("-", 80) . "\n";
    }
    
    echo "\n";
    echo "💡 TIP: Check logs for more details:\n";
    echo "   tail -f storage/logs/laravel.log | grep 'VirtualAccount'\n\n";
}

if ($successCount > 0) {
    echo "✅ Virtual accounts created successfully!\n";
    echo "Customers can now receive payments to their virtual accounts.\n\n";
}

echo "Done!\n";
