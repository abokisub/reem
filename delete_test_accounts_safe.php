<?php
/**
 * Safe script to delete test virtual accounts for KoboPoint
 * This script will:
 * 1. List all accounts
 * 2. Identify potential test accounts
 * 3. Ask for confirmation before deleting
 */

// Run this with: php delete_test_accounts_safe.php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Company;
use App\Models\VirtualAccount;
use App\Models\CompanyUser;

echo "==========================================\n";
echo "KoboPoint Test Accounts Cleanup\n";
echo "==========================================\n\n";

// Find KoboPoint company
$company = Company::where('business_id', '3450968aa027e86e3ff5b0169dc17edd7694a846')->first();

if (!$company) {
    echo "❌ Company not found!\n";
    exit(1);
}

echo "Company: {$company->name} (ID: {$company->id})\n";
echo "Email: {$company->email}\n\n";

// Get all virtual accounts
$accounts = VirtualAccount::where('company_id', $company->id)
    ->with('customer')
    ->get();

echo "Total Virtual Accounts: {$accounts->count()}\n\n";

if ($accounts->count() === 0) {
    echo "No virtual accounts found.\n";
    exit(0);
}

// Identify test accounts
$testAccounts = [];
$realAccounts = [];

foreach ($accounts as $account) {
    $isTest = false;
    $reason = '';
    
    if ($account->customer) {
        $name = strtolower($account->customer->first_name . ' ' . $account->customer->last_name);
        $email = strtolower($account->customer->email);
        
        // Check for test indicators
        if (strpos($name, 'test') !== false) {
            $isTest = true;
            $reason = 'Name contains "test"';
        } elseif (strpos($email, 'test') !== false) {
            $isTest = true;
            $reason = 'Email contains "test"';
        } elseif (strpos($name, 'demo') !== false) {
            $isTest = true;
            $reason = 'Name contains "demo"';
        } elseif (strpos($email, 'demo') !== false) {
            $isTest = true;
            $reason = 'Email contains "demo"';
        } elseif (strpos($name, 'sample') !== false) {
            $isTest = true;
            $reason = 'Name contains "sample"';
        } elseif (strpos($email, 'example.com') !== false) {
            $isTest = true;
            $reason = 'Email is example.com';
        }
    }
    
    if ($isTest) {
        $testAccounts[] = ['account' => $account, 'reason' => $reason];
    } else {
        $realAccounts[] = $account;
    }
}

// Display results
echo "==========================================\n";
echo "REAL ACCOUNTS (will be kept): " . count($realAccounts) . "\n";
echo "==========================================\n\n";

foreach ($realAccounts as $index => $account) {
    echo "✓ Account #" . ($index + 1) . " (ID: {$account->id})\n";
    echo "  Account Number: {$account->account_number}\n";
    echo "  Account Name: {$account->account_name}\n";
    if ($account->customer) {
        echo "  Customer: {$account->customer->first_name} {$account->customer->last_name}\n";
        echo "  Email: {$account->customer->email}\n";
        echo "  Phone: {$account->customer->phone}\n";
    }
    echo "  Status: {$account->status}\n";
    echo "  Created: {$account->created_at}\n\n";
}

echo "==========================================\n";
echo "TEST ACCOUNTS (will be deleted): " . count($testAccounts) . "\n";
echo "==========================================\n\n";

if (count($testAccounts) === 0) {
    echo "No test accounts found. All accounts look real.\n";
    exit(0);
}

foreach ($testAccounts as $index => $item) {
    $account = $item['account'];
    $reason = $item['reason'];
    
    echo "⚠️  Test Account #" . ($index + 1) . " (ID: {$account->id})\n";
    echo "  Reason: {$reason}\n";
    echo "  Account Number: {$account->account_number}\n";
    echo "  Account Name: {$account->account_name}\n";
    if ($account->customer) {
        echo "  Customer: {$account->customer->first_name} {$account->customer->last_name}\n";
        echo "  Email: {$account->customer->email}\n";
        echo "  Phone: {$account->customer->phone}\n";
    }
    echo "  Status: {$account->status}\n";
    echo "  Created: {$account->created_at}\n\n";
}

// Ask for confirmation
echo "==========================================\n";
echo "CONFIRMATION REQUIRED\n";
echo "==========================================\n\n";
echo "This will DELETE " . count($testAccounts) . " test account(s).\n";
echo "Real accounts (" . count($realAccounts) . ") will be kept.\n\n";
echo "Type 'DELETE' to confirm (case-sensitive): ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if ($confirmation !== 'DELETE') {
    echo "\n❌ Deletion cancelled. No accounts were deleted.\n";
    exit(0);
}

// Delete test accounts
echo "\n==========================================\n";
echo "Deleting test accounts...\n";
echo "==========================================\n\n";

$deleted = 0;
foreach ($testAccounts as $item) {
    $account = $item['account'];
    try {
        echo "Deleting: {$account->account_name} (ID: {$account->id})... ";
        
        // Also delete the customer if they have no other accounts
        $customerId = $account->customer_id;
        $account->delete();
        
        if ($customerId) {
            $otherAccounts = VirtualAccount::where('customer_id', $customerId)->count();
            if ($otherAccounts === 0) {
                $customer = CompanyUser::find($customerId);
                if ($customer) {
                    $customer->delete();
                    echo "✓ (customer also deleted)\n";
                } else {
                    echo "✓\n";
                }
            } else {
                echo "✓\n";
            }
        } else {
            echo "✓\n";
        }
        
        $deleted++;
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

echo "\n==========================================\n";
echo "Cleanup Complete!\n";
echo "==========================================\n\n";
echo "Deleted: {$deleted} test account(s)\n";
echo "Remaining: " . count($realAccounts) . " real account(s)\n\n";
echo "✓ Done!\n";
